<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/admin_db.php';
require_once __DIR__ . '/monitoring.php';
require_once __DIR__ . '/two_factor.php';
require_once __DIR__ . '/development.php';
et_register_fatal_error_monitoring();

const ET_ADMIN_IDLE_TIMEOUT = 1800;
const ET_ADMIN_ABSOLUTE_TIMEOUT = 28800;

/** Database timestamps remain UTC; display admin timestamps in Tanzania time. */
function et_admin_datetime(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '—';
    }
    try {
        return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('Africa/Dar_es_Salaam'))
            ->format('Y-m-d H:i:s') . ' EAT';
    } catch (Exception) {
        return '—';
    }
}

function et_admin_boot(): void
{
    grf_start_session('Strict', 'elimutaifa_admin');
    header('X-Robots-Tag: noindex, nofollow', true);
    header('Cache-Control: no-store, max-age=0', true);

    if (!isset($_SESSION['et_admin_id'])) {
        return;
    }

    $now = time();
    $startedAt = (int) ($_SESSION['et_admin_started_at'] ?? 0);
    $lastActivity = (int) ($_SESSION['et_admin_last_activity'] ?? 0);
    if (
        $startedAt <= 0
        || $lastActivity <= 0
        || $now - $startedAt > ET_ADMIN_ABSOLUTE_TIMEOUT
        || $now - $lastActivity > ET_ADMIN_IDLE_TIMEOUT
    ) {
        et_admin_logout();
        et_flash('error', 'Your session has expired. Sign in again.');
        return;
    }

    $_SESSION['et_admin_last_activity'] = $now;
}

function et_admin_user(): ?array
{
    $userId = (int) ($_SESSION['et_admin_id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    $statement = et_db()->prepare(
        'SELECT id, username, display_name, role, is_active, last_login_at, deleted_at FROM admin_users WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();

    if (!$user || (int) $user['is_active'] !== 1 || $user['deleted_at'] !== null) {
        et_admin_logout();
        return null;
    }

    $mfa = et_mfa_state($userId);
    if (!$mfa && isset($_SESSION['et_admin_mfa'])) {
        et_admin_logout();
        return null;
    }
    if ($mfa && !hash_equals(hash('sha256', $mfa['secret']), (string) ($_SESSION['et_admin_mfa'] ?? ''))) {
        et_admin_logout();
        return null;
    }

    return $user;
}

function et_require_owner(array $user): void
{
    if (($user['role'] ?? '') !== 'owner') {
        http_response_code(403);
        exit('Owner access required.');
    }
}

function et_require_admin(): array
{
    $user = et_admin_user();
    if ($user === null) {
        et_redirect('../login.php');
    }

    et_require_mfa_setup($user);
    return $user;
}

function et_require_admin_at_root(): array
{
    $user = et_admin_user();
    if ($user === null) {
        et_redirect('login.php');
    }

    et_require_mfa_setup($user);
    return $user;
}

function et_require_mfa_setup(array $user): void
{
    if (et_mfa_state((int) $user['id'])) { return; }
    if (et_owner_development_exception($user)) { return; }
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_ends_with($script, '/admin/two-factor.php')) { return; }
    $position = strpos($script, '/admin/');
    $base = $position === false ? '' : substr($script, 0, $position);
    et_redirect($base . '/admin/two-factor.php');
}

function et_attempt_admin_login(string $username, string $password): array
{
    $username = trim($username);
    $attemptKey = hash('sha256', strtolower($username) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $now = time();
    $database = et_db();

    $attemptStatement = $database->prepare(
        'SELECT attempts, first_attempt_at, blocked_until FROM login_attempts WHERE attempt_key = :attempt_key'
    );
    $attemptStatement->execute(['attempt_key' => $attemptKey]);
    $attempt = $attemptStatement->fetch();
    if ($attempt && (int) $attempt['blocked_until'] > $now) {
        return ['ok' => false, 'message' => 'Sign-in is temporarily blocked. Wait a few minutes, then try again.'];
    }

    $statement = $database->prepare(
        'SELECT id, username, display_name, password_hash, role, is_active, deleted_at FROM admin_users WHERE username = :username LIMIT 1'
    );
    $statement->execute(['username' => $username]);
    $user = $statement->fetch();
    $validPassword = $user
        ? password_verify($password, (string) $user['password_hash'])
        : password_verify($password, '$2y$10$4Vq5R9WVeFuOtFEYz7f0hOzrL6m6vQIUZMMs3N.8iV7z5WF5mB9Qq');

    if (!$user || (int) $user['is_active'] !== 1 || $user['deleted_at'] !== null || !$validPassword) {
        et_record_login_failure($database, $attemptKey, $attempt, $now);
        et_audit(null, 'login_failed', 'admin_user', null, 'Username: ' . mb_substr($username, 0, 80));
        return ['ok' => false, 'message' => 'Incorrect username or password.'];
    }

    $database->prepare('DELETE FROM login_attempts WHERE attempt_key = :attempt_key')
        ->execute(['attempt_key' => $attemptKey]);

    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
        $database->prepare('UPDATE admin_users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'updated_at' => et_utc_now(),
                'id' => (int) $user['id'],
            ]);
    }

    $mfa = et_mfa_state((int) $user['id']);
    if ($mfa) {
        if (!et_mfa_rate_allowed((int) $user['id'])) {
            return ['ok' => false, 'message' => '2FA is temporarily blocked for this connection. Wait up to 5 minutes, then try again.'];
        }
        et_admin_logout();
        $_SESSION['et_mfa_pending'] = ['id' => (int) $user['id'], 'expires' => time() + 300,
            'secret_hash' => hash('sha256', $mfa['secret']), 'failures' => 0,
            'source_key' => et_mfa_rate_key((int) $user['id'])];
        return ['ok' => true, 'mfa_required' => true, 'message' => ''];
    }
    et_complete_admin_login($user);
    return ['ok' => true, 'message' => ''];
}

function et_complete_admin_login(array $user, ?string $mfaHash = null): void
{
    $database = et_db();
    $now = time();
    session_regenerate_id(true);
    unset($_SESSION['et_mfa_pending'], $_SESSION['et_mfa_setup']);
    if ($mfaHash !== null) { $_SESSION['et_admin_mfa'] = $mfaHash; }
    else { unset($_SESSION['et_admin_mfa']); }
    $_SESSION['et_admin_id'] = (int) $user['id'];
    $_SESSION['et_admin_started_at'] = $now;
    $_SESSION['et_admin_last_activity'] = $now;
    $database->prepare('UPDATE admin_users SET last_login_at = :last_login_at WHERE id = :id')
        ->execute(['last_login_at' => et_utc_now(), 'id' => (int) $user['id']]);
    et_audit((int) $user['id'], 'login_succeeded', 'admin_user', (int) $user['id']);

}

function et_record_login_failure(PDO $database, string $attemptKey, array|false $attempt, int $now, int $window = 900): void
{
    $firstAttempt = $attempt ? (int) $attempt['first_attempt_at'] : $now;
    $attempts = $attempt ? (int) $attempt['attempts'] + 1 : 1;
    if ($now - $firstAttempt > $window) {
        $firstAttempt = $now;
        $attempts = 1;
    }

    $blockedUntil = $attempts >= 5 ? $now + $window : 0;
    $statement = $database->prepare(et_conflict_sql($database, <<<'SQL'
INSERT INTO login_attempts (attempt_key, attempts, first_attempt_at, blocked_until)
VALUES (:attempt_key, :attempts, :first_attempt_at, :blocked_until)
ON CONFLICT(attempt_key) DO UPDATE SET
    attempts = excluded.attempts,
    first_attempt_at = excluded.first_attempt_at,
    blocked_until = excluded.blocked_until
SQL));
    $statement->execute([
        'attempt_key' => $attemptKey,
        'attempts' => $attempts,
        'first_attempt_at' => $firstAttempt,
        'blocked_until' => $blockedUntil,
    ]);
}

function et_admin_logout(): void
{
    unset(
        $_SESSION['et_admin_id'],
        $_SESSION['et_admin_started_at'],
        $_SESSION['et_admin_last_activity'],
        $_SESSION['et_admin_mfa'],
        $_SESSION['et_mfa_pending'],
        $_SESSION['et_mfa_setup'],
        $_SESSION['et_mfa_recovery'],
        $_SESSION['et_csrf_token']
    );
    session_regenerate_id(true);
}

function et_csrf_token(): string
{
    if (!isset($_SESSION['et_csrf_token']) || !is_string($_SESSION['et_csrf_token'])) {
        $_SESSION['et_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['et_csrf_token'];
}

function et_verify_csrf(?string $token): bool
{
    $stored = $_SESSION['et_csrf_token'] ?? '';
    return is_string($token) && is_string($stored) && $stored !== '' && hash_equals($stored, $token);
}

function et_audit(?int $adminUserId, string $action, string $entityType, ?int $entityId = null, string $details = ''): void
{
    $statement = et_db()->prepare(<<<'SQL'
INSERT INTO audit_logs (admin_user_id, action, entity_type, entity_id, details, created_at)
VALUES (:admin_user_id, :action, :entity_type, :entity_id, :details, :created_at)
SQL);
    $statement->execute([
        'admin_user_id' => $adminUserId,
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'details' => mb_substr($details, 0, 500),
        'created_at' => et_utc_now(),
    ]);
}

/** Explicit operational allow-list: new or account/security events default to owner-only. */
function et_audit_visibility_sql(array $user): string
{
    if (($user['role'] ?? '') === 'owner') { return '1=1'; }
    if (($user['role'] ?? '') !== 'admin') { return '1=0'; }
    return "((audit_logs.entity_type='content_item' AND audit_logs.action IN
        ('content_created','content_updated','content_archived','content_deleted'))
        OR (audit_logs.entity_type='submission' AND audit_logs.action='submission_updated')
        OR (audit_logs.entity_type='system_event' AND audit_logs.action IN ('system_event_open','system_event_resolved'))
        OR (audit_logs.entity_type='placement_item' AND audit_logs.action IN ('placement_created','placement_updated')))";
}

function et_flash(string $type, string $message): void
{
    $_SESSION['et_flash'] = ['type' => $type, 'message' => $message];
}

function et_take_flash(): ?array
{
    $flash = $_SESSION['et_flash'] ?? null;
    unset($_SESSION['et_flash']);
    return is_array($flash) ? $flash : null;
}

function et_redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

function et_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
