<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once __DIR__ . '/_layout.php';
et_admin_boot();
$user = et_require_admin_at_root();
$id = (int) $user['id'];
$state = et_mfa_state($id);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $statement = et_db()->prepare('SELECT password_hash FROM admin_users WHERE id=?');
    $statement->execute([$id]);
    if (!et_verify_csrf($_POST['csrf_token'] ?? null)) { $error = 'Pakia ukurasa upya.'; }
    elseif (!et_mfa_rate_allowed($id)) { $error = 'Too many attempts. Wait up to 5 minutes. The remaining time is shown below.'; }
    elseif (!password_verify((string) ($_POST['password'] ?? ''), (string) $statement->fetchColumn())) {
        et_mfa_failed($id); $error = 'Incorrect password.';
    } elseif ($action === 'start' && !$state) {
        $_SESSION['et_mfa_setup'] = ['id' => $id, 'secret' => et_totp()->createSecret(), 'expires' => time() + 600];
        et_redirect('two-factor.php');
    } elseif ($action === 'enable' && !$state) {
        $setup = $_SESSION['et_mfa_setup'] ?? [];
        $slice = 0;
        $code = trim((string) ($_POST['code'] ?? ''));
        if (($setup['id'] ?? 0) !== $id || empty($setup['secret'])) {
            $error = 'Setup was not found. Start again in this tab and use the new setup key in your authenticator.';
        } elseif (($setup['expires'] ?? 0) < time()) {
            $error = 'Setup expired after 10 minutes. Start again and replace the setup key in your authenticator.';
        } elseif (!preg_match('/^\d{6}$/D', $code)) {
            et_mfa_failed($id);
            $error = 'Enter the 6-digit authenticator code, not the setup key or a recovery code.';
        } elseif (!et_totp()->verifyCode($setup['secret'], $code, 1, null, $slice)) {
            et_mfa_failed($id);
            $error = 'The code does not match this setup. Use the current setup key with TOTP, SHA-1, 6 digits and a 30-second interval. Check your device clock, then try a new code.';
        } else {
            $database = et_db();
            $database->beginTransaction();
            try {
                $encrypted = et_mfa_encrypt($setup['secret'], $id);
                $database->prepare('INSERT INTO admin_two_factor(admin_user_id,secret,last_counter,enabled_at) VALUES(?,?,?,?)')
                    ->execute([$id, $encrypted, $slice, et_utc_now()]);
                $codes = et_mfa_recovery_codes($id);
                et_audit($id, 'two_factor_enabled', 'admin_user', $id);
                $database->commit();
                $_SESSION['et_admin_mfa'] = hash('sha256', $encrypted);
                $_SESSION['et_mfa_recovery'] = $codes;
                unset($_SESSION['et_mfa_setup']);
                session_regenerate_id(true);
                et_redirect('two-factor.php');
            } catch (Throwable $exception) {
                if ($database->inTransaction()) { $database->rollBack(); }
                $error = 'Could not save 2FA. Try again.';
            }
        }
    } elseif ($state && in_array($action, ['regenerate', 'disable'], true)) {
        if ($action === 'disable') { $error = 'All admins need 2FA. A developer can reset it using the command line.'; }
        elseif (!et_mfa_consume($id, (string) ($_POST['code'] ?? ''))) {
            et_mfa_failed($id); $error = 'The code is incorrect or has already been used.';
        } else {
            $database = et_db(); $database->beginTransaction();
            try {
                if ($action === 'regenerate') { $codes = et_mfa_recovery_codes($id); }
                else {
                    $database->prepare('DELETE FROM admin_recovery_codes WHERE admin_user_id=?')->execute([$id]);
                    $database->prepare('DELETE FROM admin_two_factor WHERE admin_user_id=?')->execute([$id]);
                    unset($_SESSION['et_admin_mfa']);
                }
                et_audit($id, 'two_factor_' . $action, 'admin_user', $id);
                $database->commit();
                if ($action === 'regenerate') { $_SESSION['et_mfa_recovery'] = $codes; }
                et_redirect('two-factor.php');
            } catch (Throwable $exception) {
                if ($database->inTransaction()) { $database->rollBack(); }
                $error = 'Mabadiliko hayajahifadhiwa.';
            }
        }
    }
}
$setup = $_SESSION['et_mfa_setup'] ?? null;
$lockRemaining = et_mfa_lock_remaining($id);
if ($setup && ($setup['expires'] < time() || $setup['id'] !== $id)) { unset($_SESSION['et_mfa_setup']); $setup = null; }
$codes = $_SESSION['et_mfa_recovery'] ?? [];
unset($_SESSION['et_mfa_recovery']);
et_admin_header('Two-factor authentication', $user, 'account');
?>
<section class="form-section" style="max-width:680px"><h2>Authenticator</h2><p>All admins must set up 2FA before using other sections. Only the owner can skip this temporarily during local development.</p>
<?php if (!$state && et_owner_development_exception($user)): ?><p><a class="admin-button secondary" href="index.php">Go to Dashboard — localhost development</a></p><?php endif; ?>
<?php if ($error): ?><div class="admin-alert error" role="alert"><?= et_e($error) ?></div><?php endif; ?>
<?php if ($lockRemaining > 0): ?><div class="admin-alert" data-mfa-countdown="<?= $lockRemaining ?>">Try again in <span data-mfa-time><?= sprintf('%02d:%02d', intdiv($lockRemaining, 60), $lockRemaining % 60) ?></span>.</div><?php endif; ?>
<?php if ($codes): ?><div class="admin-alert"><h3>Save your recovery codes now</h3><p>These codes are shown only once. Each code works once. Keep them somewhere safe, separate from your phone. The downloaded file is not encrypted. Do not share it.</p><pre id="recoveryCodes"><?= et_e(implode("\n", $codes)) ?></pre><button type="button" class="admin-button secondary" data-download-recovery>Download backup codes (.txt)</button><span data-recovery-download-status role="status"></span></div><?php endif; ?>
<?php if ($state): ?><p><strong>2FA is enabled.</strong></p><form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><div class="form-field"><label>Current password<input name="password" type="password" autocomplete="current-password" required></label></div><div class="form-field"><label>Authenticator / recovery code<input name="code" autocomplete="one-time-code" maxlength="23" required></label></div><button class="admin-button" name="action" value="regenerate">Create new recovery codes</button></form><p><a href="index.php">Go to Dashboard</a></p>
<?php else: ?>
<?php if ($setup): ?><p>Scan the QR code with your authenticator. Do not share the QR code or setup key.</p><img src="<?= et_e(et_totp()->getQRCodeImageAsDataUri('ElimuTaifa:' . $user['username'], $setup['secret'], 240)) ?>" width="240" height="240" alt="Authenticator setup QR code"><p>Or enter this setup key manually: <code><?= et_e($setup['secret']) ?></code></p><?php endif; ?>
<form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="action" value="<?= $setup ? 'enable' : 'start' ?>"><div class="form-field"><label>Current password<input name="password" type="password" autocomplete="current-password" required></label></div><?php if ($setup): ?><div class="form-field"><label>6-digit code<input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required></label></div><?php endif; ?><button class="admin-button"><?= $setup ? 'Confirm and enable 2FA' : 'Set up 2FA' ?></button></form>
<?php endif; ?></section>
<?php et_admin_footer(); ?>
