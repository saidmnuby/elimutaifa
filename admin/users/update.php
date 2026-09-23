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
$role = (string) ($_POST['role'] ?? '');
$isActive = filter_var($_POST['is_active'] ?? null, FILTER_VALIDATE_INT);
if ($id <= 0 || $id === (int) $user['id'] || !in_array($role, ['owner','admin'], true) || !in_array($isActive, [0,1], true)) {
    et_flash('error', 'Admin update haikukubalika.'); et_redirect('./');
}
$database = et_db();
$statement = $database->prepare('SELECT role,is_active FROM admin_users WHERE id=:id AND deleted_at IS NULL');
$statement->execute(['id' => $id]);
$target = $statement->fetch();
if (!$target) { et_flash('error', 'Admin hajapatikana.'); et_redirect('./'); }
if ($target['role'] === 'owner' && (int) $target['is_active'] === 1 && ($role !== 'owner' || $isActive === 0)) {
    $activeOwners = (int) $database->query("SELECT COUNT(*) FROM admin_users WHERE role='owner' AND is_active=1 AND deleted_at IS NULL")->fetchColumn();
    if ($activeOwners <= 1) { et_flash('error', 'You cannot remove the last active owner.'); et_redirect('./'); }
}
$database->prepare('UPDATE admin_users SET role=:role,is_active=:is_active,updated_at=:updated_at WHERE id=:id')->execute(['role'=>$role,'is_active'=>$isActive,'updated_at'=>et_utc_now(),'id'=>$id]);
et_audit((int) $user['id'], 'admin_updated', 'admin_user', $id, 'Role: ' . $role . '; active: ' . $isActive);
et_flash('success', 'Admin account updated.'); et_redirect('./');
