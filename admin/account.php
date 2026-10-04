<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once __DIR__ . '/_layout.php';
et_admin_boot();
$user = et_require_admin_at_root();
$error = '';
$database = et_db();
$accountEmail = et_email_setting($database,'admin_email_address_'.$user['id']) ?? '';
$owner = $user['role']==='owner';
$mainOwner=et_email_main_owner($database,(int)$user['id']);
$senderRaw = $mainOwner ? et_email_setting($database,'admin_email_sender') : null;
$sender = $mainOwner ? ($senderRaw!==null ? json_decode($senderRaw,true) : et_email_config()) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!et_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'This request has expired. Reload the page.';
    } elseif (($_POST['operation'] ?? '') === 'email') {
        try {
            $accountEmail=et_email_address((string)($_POST['email'] ?? ''));
            et_email_save($database,'admin_email_address_'.$user['id'],$accountEmail);
            et_audit((int)$user['id'],'account_email_changed','admin_user',(int)$user['id']);
            et_flash('success','Account email saved.'); et_redirect('account.php');
        } catch (Throwable) { $error='Enter a valid email address.'; }
    } elseif (($_POST['operation'] ?? '') === 'email_setup') {
        et_require_owner($user);
        try {
            et_email_require_main_owner($database,(int)$user['id']);
            $email = (string)($_POST['sender_email'] ?? '');
            if ($accountEmail==='') throw new InvalidArgumentException('Save your notification email before testing the system sender.');
            et_email_setup($database,(int)$user['id'],(string)($_POST['provider'] ?? ''),$email,(string)($_POST['email_secret'] ?? ''),$accountEmail,null,(string)($_POST['sender_revision'] ?? ''));
            et_audit((int)$user['id'],'email_setup_succeeded','email',null,'Provider: '.($_POST['provider'] ?? ''));
            et_flash('success','Setup successful. The provider accepted a test email to your account email.'); et_redirect('account.php');
        } catch (Throwable $e) { $error=$e instanceof PDOException?'Settings could not be saved. Please try again.':$e->getMessage(); }
    } elseif (($_POST['operation'] ?? '') === 'email_pause') {
        if (!$mainOwner) { http_response_code(403); exit('Only the main owner can change system email settings.'); }
        et_email_save($database,'admin_email_enabled','0');
        et_audit((int)$user['id'],'email_notifications_paused','email');
        et_flash('success','Email notifications paused.'); et_redirect('account.php');
    } else {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmation = (string) ($_POST['new_password_confirmation'] ?? '');
        $statement = et_db()->prepare('SELECT password_hash FROM admin_users WHERE id=:id LIMIT 1');
        $statement->execute(['id' => (int) $user['id']]);
        $hash = (string) $statement->fetchColumn();
        if (!password_verify($currentPassword, $hash)) {
            $error = 'The current password is incorrect.';
        } elseif (strlen($newPassword) < 12 || strlen($newPassword) > 200) {
            $error = 'Use 12 to 200 characters for the new password.';
        } elseif ($newPassword !== $confirmation) {
            $error = 'The new passwords do not match.';
        } elseif (hash_equals($currentPassword, $newPassword)) {
            $error = 'Choose a password different from the current one.';
        } else {
            et_db()->prepare('UPDATE admin_users SET password_hash=:password_hash, updated_at=:updated_at WHERE id=:id')
                ->execute(['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'updated_at' => et_utc_now(), 'id' => (int) $user['id']]);
            session_regenerate_id(true);
            $_SESSION['et_admin_started_at'] = time();
            $_SESSION['et_admin_last_activity'] = time();
            et_audit((int) $user['id'], 'password_changed', 'admin_user', (int) $user['id']);
            et_flash('success', 'Password changed.');
            et_redirect('account.php');
        }
    }
}

$connected = $mainOwner && et_email_ready() && et_email_setting($database,'admin_email_enabled')==='1';
$providerName = ($sender['provider'] ?? 'gmail')==='brevo' ? 'Brevo' : 'Gmail';
$mfaEnabled = (bool) et_mfa_state((int)$user['id']);
$operation = (string)($_POST['operation'] ?? 'password');
$initials = mb_strtoupper(mb_substr($user['display_name'],0,1));
et_admin_header('Account', $user, 'account');
?>
<link rel="stylesheet" href="assets/account.css">
<div class="account-page">
    <div class="account-intro"><h2>Your account</h2><p>Manage your contact email, notifications and sign-in security.</p></div>
    <?php if ($error !== ''): ?><div class="admin-alert error" role="alert"><?= et_e($error) ?></div><?php endif; ?>
    <section class="account-profile" aria-label="Account overview">
        <span class="account-avatar" aria-hidden="true"><?= et_e($initials) ?></span>
        <div class="account-identity"><h2><?= et_e($user['display_name']) ?></h2><p>@<?= et_e($user['username']) ?> <span class="account-role"><?= et_e(ucfirst($user['role'])) ?></span></p></div>
        <div class="account-last-login"><span>Last sign-in</span><strong><?= et_e(et_admin_datetime($user['last_login_at'] ?? null)) ?></strong></div>
    </section>
    <div class="account-layout">
        <div class="account-stack">
            <section class="account-card">
                <div class="account-card-heading"><span class="account-icon" aria-hidden="true">↗</span><div><h2>Email and notifications</h2><p>Your notification email belongs to your account.</p></div></div>
                <form method="post" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="operation" value="email">
                    <div class="form-field"><label for="account_email">Your notification email</label><input id="account_email" name="email" type="email" maxlength="254" value="<?= et_e($accountEmail) ?>" autocomplete="email"><small>Changing this updates only your account. Leave blank to stop receiving emails.</small></div>
                    <div class="account-actions"><button class="admin-button secondary" type="submit">Save my email</button></div>
                </form>
                <?php if ($mainOwner): ?>
                <h3>System email sender</h3><p>Only the main owner can manage this connection. It sends email for all accounts without changing their notification emails.</p>
                <div class="account-connection <?= $connected?'is-connected':'' ?>"><span class="account-status-dot" aria-hidden="true"></span><div><strong><?= $connected?'Setup successful':'Not connected' ?></strong><p><?= $connected?et_e($providerName).' is connected. Notifications are on.':'Connect Gmail or Brevo to get started.' ?></p><?php if ($connected): ?><p><?= et_e($sender['from_email'] ?? '') ?></p><?php endif; ?></div></div>
                <details class="account-details"<?= !$connected || ($operation==='email_setup' && $error!=='')?' open':'' ?>>
                    <summary><?= $connected?'Change system sender':'Set up system sender' ?><span aria-hidden="true">+</span></summary>
                    <form method="post" class="admin-form" data-email-setup>
                        <input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="operation" value="email_setup"><input type="hidden" name="sender_revision" value="<?= et_e(hash('sha256',$senderRaw ?? '')) ?>">
                        <div class="form-grid">
                            <div class="form-field"><label for="email_provider">Provider</label><select id="email_provider" name="provider"><option value="gmail"<?= ($sender['provider'] ?? 'gmail')==='gmail'?' selected':'' ?>>Gmail</option><option value="brevo"<?= ($sender['provider'] ?? '')==='brevo'?' selected':'' ?>>Brevo</option></select></div>
                            <div class="form-field"><label for="sender_email">Sender email</label><input id="sender_email" name="sender_email" type="email" value="<?= et_e($sender['from_email'] ?? '') ?>" required maxlength="254"><small>Used to send system email. Your notification email above stays separate.</small></div>
                        </div>
                        <p class="account-help" data-email-help="gmail">Enable Google 2-Step Verification, then create an <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener noreferrer">App Password</a> named ElimuTaifa. Enter its 16-character code below, instead of your normal Gmail password.</p>
                        <p class="account-help" data-email-help="brevo" hidden>Verify your sender email in Brevo. Create an API key under SMTP &amp; API → API Keys and enter it below. Use an API key, not an SMTP key.</p>
                        <div class="form-field"><label for="email_secret" data-email-secret-label>Google App Password</label><input id="email_secret" name="email_secret" type="password" autocomplete="new-password" maxlength="512"><small>Leave blank to keep the saved code for the same sender.</small></div>
                        <p class="account-test-note">We will send a test to your saved notification email and save this system connection only if the test succeeds.</p>
                        <div class="account-actions"><button class="admin-button" type="submit">Test connection</button></div><p data-email-test-status role="status" hidden>Testing connection. Please wait…</p>
                    </form>
                </details>
                <?php if ($connected): ?><form method="post" class="account-pause"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="operation" value="email_pause"><button class="admin-button secondary small" type="submit">Pause all system emails</button></form><?php endif; ?>
                <script src="assets/account-email.js" defer></script>
                <?php else: ?><p>The main owner manages system email delivery. You only need your notification email.</p><?php endif; ?>
            </section>
        </div>
        <section class="account-card account-security">
            <div class="account-card-heading"><span class="account-icon" aria-hidden="true">✓</span><div><h2>Account security</h2><p>Keep your sign-in details safe.</p></div></div>
            <div class="account-security-row"><div><strong>Two-step sign-in</strong><p>Use a second code when signing in.</p></div><span class="account-badge <?= $mfaEnabled?'is-enabled':'' ?>"><?= $mfaEnabled?'Enabled':'Not enabled' ?></span></div>
            <a class="admin-button secondary" href="two-factor.php"><?= $mfaEnabled?'Manage two-step sign-in':'Set up two-step sign-in' ?></a>
            <details class="account-details account-password"<?= $error!=='' && $operation==='password'?' open':'' ?>>
                <summary>Change password<span aria-hidden="true">+</span></summary>
                <form method="post" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="operation" value="password">
                    <div class="form-field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
                    <div class="form-field"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" minlength="12" maxlength="200" autocomplete="new-password" required><small>Use at least 12 characters.</small></div>
                    <div class="form-field"><label for="new_password_confirmation">Confirm new password</label><input id="new_password_confirmation" name="new_password_confirmation" type="password" minlength="12" maxlength="200" autocomplete="new-password" required></div>
                    <div class="account-actions"><button class="admin-button" type="submit">Update password</button></div>
                </form>
            </details>
        </section>
    </div>
</div>
<?php et_admin_footer(); ?>
