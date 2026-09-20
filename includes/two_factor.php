<?php
declare(strict_types=1);
require_once __DIR__ . '/admin_db.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

function et_totp(): RobThree\Auth\TwoFactorAuth
{
    return new RobThree\Auth\TwoFactorAuth(new RobThree\Auth\Providers\Qr\BaconQrCodeProvider(format: 'svg'), 'ElimuTaifa');
}

function et_mfa_key(): string
{
    $key = getenv('ELIMUTAIFA_2FA_KEY');
    if ($key === false) {
        $file = dirname(__DIR__) . '/storage/two-factor-key.php';
        $key = is_file($file) ? require $file : '';
    }
    $decoded = is_string($key) ? base64_decode($key, true) : false;
    if ($decoded === false || strlen($decoded) !== 32) {
        throw new RuntimeException('The protected 2FA encryption key is unavailable.');
    }
    return $decoded;
}

function et_mfa_encrypt(string $secret, int $userId): string
{
    $iv = random_bytes(12);
    $encrypted = openssl_encrypt($secret, 'aes-256-gcm', et_mfa_key(), OPENSSL_RAW_DATA, $iv, $tag, 'admin:' . $userId);
    if ($encrypted === false) { throw new RuntimeException('Unable to encrypt 2FA secret.'); }
    return base64_encode($iv . $tag . $encrypted);
}

function et_mfa_decrypt(string $value, int $userId): string
{
    $bytes = base64_decode($value, true);
    if ($bytes === false || strlen($bytes) < 29) { throw new RuntimeException('Invalid encrypted 2FA secret.'); }
    $secret = openssl_decrypt(substr($bytes, 28), 'aes-256-gcm', et_mfa_key(), OPENSSL_RAW_DATA,
        substr($bytes, 0, 12), substr($bytes, 12, 16), 'admin:' . $userId);
    if ($secret === false) { throw new RuntimeException('Unable to decrypt 2FA secret.'); }
    return $secret;
}

function et_mfa_state(int $id): array|false
{
    $statement = et_db()->prepare('SELECT * FROM admin_two_factor WHERE admin_user_id=?');
    $statement->execute([$id]);
    return $statement->fetch();
}

/** Atomic counter update prevents simultaneous reuse of a valid TOTP. */
function et_mfa_consume(int $id, string $code): bool
{
    $state = et_mfa_state($id);
    if (!$state) { return false; }
    $code = trim($code);
    if (preg_match('/^\d{6}$/D', $code)) {
        $slice = 0;
        if (!et_totp()->verifyCode(et_mfa_decrypt($state['secret'], $id), $code, 1, null, $slice)) { return false; }
        $statement = et_db()->prepare('UPDATE admin_two_factor SET last_counter=? WHERE admin_user_id=? AND last_counter < ? AND secret=?');
        $statement->execute([$slice, $id, $slice, $state['secret']]);
        return $statement->rowCount() === 1;
    }
    $normalised = strtoupper(str_replace('-', '', $code));
    if (!preg_match('/^[A-F0-9]{20}$/D', $normalised)) { return false; }
    $statement = et_db()->prepare('DELETE FROM admin_recovery_codes WHERE admin_user_id=? AND code_hash=?');
    $statement->execute([$id, hash('sha256', $normalised)]);
    return $statement->rowCount() === 1;
}

function et_mfa_recovery_codes(int $id): array
{
    et_db()->prepare('DELETE FROM admin_recovery_codes WHERE admin_user_id=?')->execute([$id]);
    $codes = [];
    $insert = et_db()->prepare('INSERT INTO admin_recovery_codes(admin_user_id,code_hash) VALUES(?,?)');
    for ($i = 0; $i < 10; $i++) {
        $code = strtoupper(bin2hex(random_bytes(10)));
        $insert->execute([$id, hash('sha256', $code)]);
        $codes[] = implode('-', str_split($code, 5));
    }
    return $codes;
}

/** Only the direct client address is trusted; forwarding headers cannot select a new bucket. */
function et_mfa_rate_key(int $id): string
{
    $address = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $packed = @inet_pton($address);
    $source = $packed === false ? 'unknown' : bin2hex($packed);
    return 'mfa:' . $id . ':' . substr(hash_hmac('sha256', $source, et_mfa_key()), 0, 32);
}

function et_mfa_pending_valid(array $pending): bool
{
    return (int) ($pending['expires'] ?? 0) > time()
        && (int) ($pending['failures'] ?? 0) < 3
        && hash_equals(et_mfa_rate_key((int) ($pending['id'] ?? 0)), (string) ($pending['source_key'] ?? ''));
}

function et_mfa_pending_failure(int $id): bool
{
    if ((int) ($_SESSION['et_mfa_pending']['id'] ?? 0) !== $id) { return true; }
    $_SESSION['et_mfa_pending']['failures'] = (int) ($_SESSION['et_mfa_pending']['failures'] ?? 0) + 1;
    if ($_SESSION['et_mfa_pending']['failures'] < 3) { return false; }
    unset($_SESSION['et_mfa_pending']);
    et_audit($id, 'two_factor_challenge_closed', 'admin_user', $id, 'Three invalid codes in a pending login.');
    return true;
}
function et_mfa_rate_allowed(int $id): bool
{
    return et_mfa_lock_remaining($id) === 0;
}
function et_mfa_lock_remaining(int $id): int
{
    $statement = et_db()->prepare('SELECT blocked_until FROM login_attempts WHERE attempt_key=?');
    $statement->execute([et_mfa_rate_key($id)]);
    return max(0, (int) $statement->fetchColumn() - time());
}
function et_mfa_failed(int $id): void
{
    $database = et_db();
    $key = et_mfa_rate_key($id);
    $now = time();
    // Increment in SQL so parallel sessions cannot overwrite each other's failure counts.
    $database->prepare(et_conflict_sql($database, <<<'SQL'
INSERT INTO login_attempts(attempt_key,attempts,first_attempt_at,blocked_until) VALUES(?,1,?,0)
ON CONFLICT(attempt_key) DO UPDATE SET
attempts=CASE WHEN login_attempts.first_attempt_at < excluded.first_attempt_at-300 THEN 1 ELSE login_attempts.attempts+1 END,
blocked_until=CASE WHEN login_attempts.first_attempt_at < excluded.first_attempt_at-300 THEN 0 ELSE login_attempts.blocked_until END,
first_attempt_at=CASE WHEN login_attempts.first_attempt_at < excluded.first_attempt_at-300 THEN excluded.first_attempt_at ELSE login_attempts.first_attempt_at END
SQL))->execute([$key, $now]);
    $database->prepare('UPDATE login_attempts SET blocked_until=? WHERE attempt_key=? AND attempts>=5 AND blocked_until=0')
        ->execute([$now + 300, $key]);
    et_audit($id, 'two_factor_failed', 'admin_user', $id);
    // Existing rate-limit rows provide bounded-window detection without storing raw IP addresses.
    $statement = et_db()->prepare('SELECT COUNT(*) AS sources, COALESCE(SUM(attempts),0) AS failures
        FROM login_attempts WHERE attempt_key LIKE ? AND first_attempt_at >= ?');
    $statement->execute(['mfa:' . $id . ':%', time() - 300]);
    $summary = $statement->fetch();
    if ((int) $summary['sources'] >= 3 && (int) $summary['failures'] >= 10) {
        et_record_system_event('two_factor_distributed_attempts',
            'Repeated invalid 2FA attempts against admin account ' . $id . ' from multiple sources within five minutes.',
            'warning', ['request_path' => '/admin/verify.php', 'error_code' => 'MFA_MULTI_SOURCE_' . $id]);
    }
}
