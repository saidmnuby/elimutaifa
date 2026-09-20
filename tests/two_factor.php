<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit; }
require_once dirname(__DIR__) . '/includes/admin_auth.php';
$sessionDirectory = sys_get_temp_dir() . '/elimutaifa-mfa-' . bin2hex(random_bytes(8));
if (!mkdir($sessionDirectory, 0700)) { throw new RuntimeException('Unable to create isolated test session directory.'); }
session_save_path($sessionDirectory);
grf_start_session('Strict', 'elimutaifa_2fa_test');
if (session_status() !== PHP_SESSION_ACTIVE) { throw new RuntimeException('Test session did not start.'); }
function check_mfa(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
$database = et_db();
$database->beginTransaction();
try {
    $rfc = new RobThree\Auth\TwoFactorAuth(new RobThree\Auth\Providers\Qr\BaconQrCodeProvider(format: 'svg'), 'Test', 8);
    check_mfa($rfc->getCode('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 59) === '94287082', 'RFC 6238 reference vector');
    $username = 'mfa-test-' . bin2hex(random_bytes(6));
    $password = bin2hex(random_bytes(16));
    $database->prepare('INSERT INTO admin_users(username,display_name,password_hash,role,is_active,created_at,updated_at) VALUES(?,?,?,\'admin\',1,?,?)')
        ->execute([$username, '2FA test', password_hash($password, PASSWORD_DEFAULT), et_utc_now(), et_utc_now()]);
    $id = (int) $database->lastInsertId();
    $secret = et_totp()->createSecret();
    $encrypted = et_mfa_encrypt($secret, $id);
    check_mfa(et_mfa_decrypt($encrypted, $id) === $secret, 'Encryption round trip');
    try { et_mfa_decrypt($encrypted, $id + 1); throw new LogicException('Wrong-account decryption accepted'); }
    catch (RuntimeException $expected) {}
    check_mfa(str_starts_with(et_totp()->getQRCodeImageAsDataUri('ElimuTaifa:test', $secret), 'data:image/svg+xml;base64,'), 'Local QR generation');
    $database->prepare('INSERT INTO admin_two_factor(admin_user_id,secret,last_counter,enabled_at) VALUES(?,?,-1,?)')->execute([$id,$encrypted,et_utc_now()]);
    $result = et_attempt_admin_login($username, $password);
    check_mfa($result['ok'] && !empty($result['mfa_required']), 'Password must lead to MFA challenge');
    check_mfa(!isset($_SESSION['et_admin_id']) && et_admin_user() === null, 'Pending login must not have admin access');
    check_mfa(!et_mfa_consume($id, 'invalid'), 'Invalid code rejected');
    $code = et_totp()->getCode($secret);
    check_mfa(et_mfa_consume($id, $code), 'Valid TOTP accepted');
    check_mfa(!et_mfa_consume($id, $code), 'TOTP replay rejected');
    $codes = et_mfa_recovery_codes($id);
    check_mfa(count($codes) === 10 && et_mfa_consume($id, $codes[0]), 'Recovery code accepted');
    check_mfa(!et_mfa_consume($id, $codes[0]), 'Recovery code replay rejected');
    et_mfa_recovery_codes($id);
    check_mfa(!et_mfa_consume($id, $codes[1]), 'Regeneration invalidates old codes');
    $statement = $database->prepare('SELECT * FROM admin_users WHERE id=?'); $statement->execute([$id]);
    et_complete_admin_login($statement->fetch(), hash('sha256', $encrypted));
    check_mfa(et_admin_user() !== null, 'Verified login has access');
    unset($_SESSION['et_admin_mfa']);
    check_mfa(et_admin_user() === null, 'Unverified old session rejected');
    for ($i=0;$i<5;$i++) { et_mfa_failed($id); }
    check_mfa(!et_mfa_rate_allowed($id), 'Five failures block account MFA attempts');
    check_mfa(et_mfa_lock_remaining($id) > 295 && et_mfa_lock_remaining($id) <= 300, 'MFA lock must last five minutes');
    $originalAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $_SERVER['REMOTE_ADDR'] = '198.51.100.10';
    check_mfa(et_mfa_rate_allowed($id), 'Another source must not inherit the account lock');
    $_SESSION['et_mfa_pending'] = ['id' => $id, 'expires' => time()+300, 'failures' => 0, 'source_key' => et_mfa_rate_key($id)];
    check_mfa(et_mfa_pending_valid($_SESSION['et_mfa_pending']), 'Pending challenge accepts its original source');
    check_mfa(!et_mfa_pending_failure($id) && !et_mfa_pending_failure($id) && et_mfa_pending_failure($id), 'Three failures close pending challenge');
    check_mfa(!isset($_SESSION['et_mfa_pending']), 'Closed challenge cannot be retried');
    for ($i=0;$i<3;$i++) { et_mfa_failed($id); }
    $_SERVER['REMOTE_ADDR'] = '198.51.100.11';
    $wrongSourcePending = ['id'=>$id, 'expires'=>time()+300, 'failures'=>0, 'source_key'=>'wrong-source'];
    check_mfa(!et_mfa_pending_valid($wrongSourcePending), 'Challenge cannot be moved to another source');
    for ($i=0;$i<2;$i++) { et_mfa_failed($id); }
    check_mfa(et_mfa_rate_allowed($id), 'Distributed failures alert without globally locking account');
    $statement = $database->prepare('SELECT COUNT(*) FROM system_events WHERE error_code=?');
    $statement->execute(['MFA_MULTI_SOURCE_' . $id]);
    check_mfa((int) $statement->fetchColumn() === 1, 'Distributed attempts produce a grouped monitoring event');
    if ($originalAddress === null) { unset($_SERVER['REMOTE_ADDR']); } else { $_SERVER['REMOTE_ADDR'] = $originalAddress; }
    $database->rollBack();
    et_admin_logout();
    session_destroy();
    rmdir($sessionDirectory);
    echo "PASS: RFC vector, encryption, local QR, partial login isolation, TOTP/recovery replay, regeneration, session enforcement and throttling. Diagnostic writes rolled back.\n";
} catch (Throwable $exception) {
    if ($database->inTransaction()) { $database->rollBack(); }
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . "\n"); exit(1);
}
