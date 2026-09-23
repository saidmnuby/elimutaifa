<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/_layout.php';
et_admin_boot();
$user = et_require_admin();
et_require_owner($user);
$database = et_db();
$errors = [];
$newValues = ['username' => '', 'display_name' => '', 'role' => 'admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newValues = [
        'username' => trim((string) ($_POST['username'] ?? '')),
        'display_name' => trim((string) ($_POST['display_name'] ?? '')),
        'role' => (string) ($_POST['role'] ?? 'admin'),
    ];
    $password = (string) ($_POST['password'] ?? '');
    if (!et_verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'This request has expired. Reload the page.';
    }
    if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $newValues['username'])) {
        $errors['username'] = 'Use 3–50 characters: letters, numbers, dots, underscores or hyphens.';
    }
    if (mb_strlen($newValues['display_name']) < 2 || mb_strlen($newValues['display_name']) > 80) {
        $errors['display_name'] = 'Use 2–80 characters for the display name.';
    }
    if (!in_array($newValues['role'], ['owner', 'admin'], true)) {
        $errors['role'] = 'Invalid role.';
    }
    if (strlen($password) < 12 || strlen($password) > 200) {
        $errors['password'] = 'Use 12–200 characters for the temporary password.';
    }
    if (!$errors) {
        try {
            $now = et_utc_now();
            $statement = $database->prepare(<<<'SQL'
INSERT INTO admin_users (username,display_name,password_hash,role,is_active,created_at,updated_at)
VALUES (:username,:display_name,:password_hash,:role,1,:created_at,:updated_at)
SQL);
            $statement->execute([
                'username' => $newValues['username'], 'display_name' => $newValues['display_name'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $newValues['role'],
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $newId = (int) $database->lastInsertId();
            et_audit((int) $user['id'], 'admin_created', 'admin_user', $newId, 'Role: ' . $newValues['role']);
            et_flash('success', 'Admin account created. Share the username and temporary password securely.');
            et_redirect('./');
        } catch (PDOException $exception) {
            $errors['form'] = str_contains(strtolower($exception->getMessage()), 'unique') ? 'This username is already used.' : 'Could not create the admin account.';
        }
    }
}

$admins = $database->query('SELECT id,username,display_name,role,is_active,last_login_at,created_at FROM admin_users WHERE deleted_at IS NULL ORDER BY is_active DESC, id ASC')->fetchAll();
et_admin_header('Admins', $user, 'users', '../');
?>
<div class="admin-page-heading"><div><p>The owner can add admins, change roles, disable accounts or remove access.</p></div></div>
<?php if (isset($errors['form'])): ?><div class="admin-alert error"><?= et_e($errors['form']) ?></div><?php endif; ?>
<section class="form-section" style="margin-bottom:18px"><h2>Add admin</h2><form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><div class="form-grid">
    <div class="form-field"><label for="username">Username</label><input id="username" name="username" maxlength="50" value="<?= et_e($newValues['username']) ?>" required><?php if (isset($errors['username'])): ?><span class="form-error"><?= et_e($errors['username']) ?></span><?php endif; ?></div>
    <div class="form-field"><label for="display_name">Display name</label><input id="display_name" name="display_name" maxlength="80" value="<?= et_e($newValues['display_name']) ?>" required><?php if (isset($errors['display_name'])): ?><span class="form-error"><?= et_e($errors['display_name']) ?></span><?php endif; ?></div>
    <div class="form-field"><label for="role">Role</label><select id="role" name="role"><option value="admin">Admin</option><option value="owner"<?= $newValues['role'] === 'owner' ? ' selected' : '' ?>>Owner</option></select></div>
    <div class="form-field"><label for="password">Temporary password</label><input id="password" name="password" type="password" minlength="12" maxlength="200" autocomplete="new-password" required><?php if (isset($errors['password'])): ?><span class="form-error"><?= et_e($errors['password']) ?></span><?php endif; ?></div>
</div><button class="admin-button" type="submit">Tengeneza admin</button></form></section>
<div class="table-wrap"><table class="admin-table"><thead><tr><th>Admin</th><th>Role/status</th><th>Last login</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($admins as $admin): ?><tr><td><strong><?= et_e($admin['display_name']) ?></strong><small><?= et_e($admin['username']) ?><?= (int) $admin['id'] === (int) $user['id'] ? ' · You' : '' ?></small></td><td><span class="status-badge <?= (int) $admin['is_active'] ? 'status-published' : 'status-archived' ?>"><?= (int) $admin['is_active'] ? 'Active' : 'Inactive' ?></span><small><?= et_e(ucfirst($admin['role'])) ?></small></td><td><?= $admin['last_login_at'] ? et_e(et_admin_datetime($admin['last_login_at'])) : 'Never' ?></td><td><?php if ((int) $admin['id'] !== (int) $user['id']): ?><div class="table-actions"><form action="update.php" method="post"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $admin['id'] ?>"><select name="role" aria-label="Role"><option value="admin"<?= $admin['role'] === 'admin' ? ' selected' : '' ?>>Admin</option><option value="owner"<?= $admin['role'] === 'owner' ? ' selected' : '' ?>>Owner</option></select><select name="is_active" aria-label="Status"><option value="1"<?= (int) $admin['is_active'] ? ' selected' : '' ?>>Active</option><option value="0"<?= !(int) $admin['is_active'] ? ' selected' : '' ?>>Inactive</option></select><button class="admin-button small" type="submit">Update</button></form><form action="remove.php" method="post" data-confirm="Remove access for this admin? Their activity records will be kept."><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $admin['id'] ?>"><button class="admin-button danger small" type="submit">Remove</button></form></div><?php else: ?><small>Use Account to change your password.</small><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php et_admin_footer(); ?>
