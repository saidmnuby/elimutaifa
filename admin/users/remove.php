<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/admin_auth.php';
et_admin_boot();
$user = et_require_admin();
et_require_owner($user);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !et_verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(405); exit('Method not allowed');
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($id <= 0 || $id === (int) $user['id']) { et_flash('error', 'Huwezi kuondoa account hii.'); et_redirect('./'); }
$database = et_db();
$statement = $database->prepare('SELECT role,is_active FROM admin_users WHERE id=:id AND deleted_at IS NULL');
$statement->execute(['id'=>$id]);
$target = $statement->fetch();
if (!$target) { et_flash('error', 'Admin hajapatikana.'); et_redirect('./'); }
if ($target['role'] === 'owner' && (int) $target['is_active'] === 1) {
    $activeOwners = (int) $database->query("SELECT COUNT(*) FROM admin_users WHERE role='owner' AND is_active=1 AND deleted_at IS NULL")->fetchColumn();
    if ($activeOwners <= 1) { et_flash('error', 'Huwezi kuondoa active owner wa mwisho.'); et_redirect('./'); }
}
$removedUsername = 'removed-' . $id . '-' . bin2hex(random_bytes(5));
$database->prepare(<<<'SQL'
UPDATE admin_users SET username=:username,display_name=:display_name,password_hash=:password_hash,
role='admin',is_active=0,deleted_at=:deleted_at,updated_at=:updated_at WHERE id=:id
SQL)->execute(['username'=>$removedUsername,'display_name'=>'Removed administrator #' . $id,'password_hash'=>password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),'deleted_at'=>et_utc_now(),'updated_at'=>et_utc_now(),'id'=>$id]);
et_audit((int) $user['id'], 'admin_removed', 'admin_user', $id, 'Access removed; historical records retained.');
et_flash('success', 'Admin access imeondolewa. Audit history imebaki.'); et_redirect('./');
