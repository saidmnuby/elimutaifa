<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/includes/admin_auth.php';
$username = trim((string) ($argv[1] ?? ''));
if ($username === '' || ($argv[2] ?? '') !== '--confirm') {
    fwrite(STDERR, "Usage: php scripts/reset_two_factor.php username --confirm\nRequires trusted server access. Verify the administrator's identity first.\n"); exit(1);
}
$database = et_db();
$statement = $database->prepare('SELECT id FROM admin_users WHERE username=? AND is_active=1 AND deleted_at IS NULL');
$statement->execute([$username]);
$id = (int) $statement->fetchColumn();
if ($id < 1) { fwrite(STDERR, "Active administrator not found.\n"); exit(1); }
$database->beginTransaction();
try {
    $database->prepare('DELETE FROM admin_recovery_codes WHERE admin_user_id=?')->execute([$id]);
    $database->prepare('DELETE FROM admin_two_factor WHERE admin_user_id=?')->execute([$id]);
    $database->prepare('DELETE FROM login_attempts WHERE attempt_key=?')->execute([et_mfa_rate_key($id)]);
    $database->prepare('DELETE FROM login_attempts WHERE attempt_key LIKE ?')->execute(['mfa:' . $id . ':%']);
    et_audit(null, 'two_factor_cli_reset', 'admin_user', $id, 'Trusted server operator reset; re-enrolment required for owner.');
    $database->commit();
    echo "2FA reset. The admin must enrol again; owner access remains restricted until enrolment.\n";
} catch (Throwable $exception) {
    if ($database->inTransaction()) { $database->rollBack(); }
    fwrite(STDERR, "Reset failed; no changes committed.\n"); exit(1);
}
