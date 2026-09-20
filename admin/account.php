<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once __DIR__ . '/_layout.php';
et_admin_boot();
$user = et_require_admin_at_root();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!et_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ombi limeisha muda. Pakia ukurasa upya.';
    } else {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmation = (string) ($_POST['new_password_confirmation'] ?? '');
        $statement = et_db()->prepare('SELECT password_hash FROM admin_users WHERE id=:id LIMIT 1');
        $statement->execute(['id' => (int) $user['id']]);
        $hash = (string) $statement->fetchColumn();
        if (!password_verify($currentPassword, $hash)) {
            $error = 'Nenosiri la sasa si sahihi.';
        } elseif (strlen($newPassword) < 12 || strlen($newPassword) > 200) {
            $error = 'Nenosiri jipya liwe na herufi 12 hadi 200.';
        } elseif ($newPassword !== $confirmation) {
            $error = 'Uthibitisho wa nenosiri jipya haulingani.';
        } elseif (hash_equals($currentPassword, $newPassword)) {
            $error = 'Nenosiri jipya liwe tofauti na la sasa.';
        } else {
            et_db()->prepare('UPDATE admin_users SET password_hash=:password_hash, updated_at=:updated_at WHERE id=:id')
                ->execute(['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'updated_at' => et_utc_now(), 'id' => (int) $user['id']]);
            session_regenerate_id(true);
            $_SESSION['et_admin_started_at'] = time();
            $_SESSION['et_admin_last_activity'] = time();
            et_audit((int) $user['id'], 'password_changed', 'admin_user', (int) $user['id']);
            et_flash('success', 'Nenosiri limebadilishwa.');
            et_redirect('account.php');
        }
    }
}

et_admin_header('Account', $user, 'account');
?>
<div class="admin-page-heading"><div><p>Badilisha nenosiri la admin aliyeingia.</p></div></div>
<section class="form-section"><h2>Ulinzi wa akaunti</h2><a class="admin-button secondary" href="two-factor.php">Simamia Authenticator (2FA)</a></section>
<?php if ($error !== ''): ?><div class="admin-alert error" role="alert"><?= et_e($error) ?></div><?php endif; ?>
<form method="post" class="admin-form" style="max-width:680px">
    <input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>">
    <section class="form-section"><h2><?= et_e($user['display_name']) ?></h2><div class="form-grid">
        <div class="form-field full"><label for="current_password">Nenosiri la sasa</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
        <div class="form-field"><label for="new_password">Nenosiri jipya</label><input id="new_password" name="new_password" type="password" minlength="12" maxlength="200" autocomplete="new-password" required></div>
        <div class="form-field"><label for="new_password_confirmation">Rudia nenosiri jipya</label><input id="new_password_confirmation" name="new_password_confirmation" type="password" minlength="12" maxlength="200" autocomplete="new-password" required></div>
    </div></section>
    <div class="form-actions"><button class="admin-button" type="submit">Badilisha nenosiri</button></div>
</form>
<?php et_admin_footer(); ?>
