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
    elseif (!et_mfa_rate_allowed($id)) { $error = 'Majaribio mengi. Lock ya 2FA ni dakika 5; angalia muda uliobaki hapa chini.'; }
    elseif (!password_verify((string) ($_POST['password'] ?? ''), (string) $statement->fetchColumn())) {
        et_mfa_failed($id); $error = 'Nenosiri si sahihi.';
    } elseif ($action === 'start' && !$state) {
        $_SESSION['et_mfa_setup'] = ['id' => $id, 'secret' => et_totp()->createSecret(), 'expires' => time() + 600];
        et_redirect('two-factor.php');
    } elseif ($action === 'enable' && !$state) {
        $setup = $_SESSION['et_mfa_setup'] ?? [];
        $slice = 0;
        $code = trim((string) ($_POST['code'] ?? ''));
        if (($setup['id'] ?? 0) !== $id || empty($setup['secret'])) {
            $error = 'Session ya setup haijapatikana. Anza setup tena kwenye tab hii; tumia setup key mpya katika Authenticator.';
        } elseif (($setup['expires'] ?? 0) < time()) {
            $error = 'Setup imekwisha muda wa dakika 10. Anza setup tena na ubadilishe setup key katika Authenticator.';
        } elseif (!preg_match('/^\d{6}$/D', $code)) {
            et_mfa_failed($id);
            $error = 'Format ya code si sahihi. Ingiza tarakimu 6 kutoka Authenticator, si setup key au recovery code.';
        } elseif (!et_totp()->verifyCode($setup['secret'], $code, 1, null, $slice)) {
            et_mfa_failed($id);
            $error = 'Code hailingani na setup hii. Hakikisha Authenticator ina setup key ya sasa, TOTP, SHA-1, tarakimu 6 na sekunde 30; pia hakikisha saa ya device ni sahihi, kisha jaribu code mpya.';
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
                $error = '2FA haijahifadhiwa. Jaribu tena.';
            }
        }
    } elseif ($state && in_array($action, ['regenerate', 'disable'], true)) {
        if ($action === 'disable') { $error = 'Admins wote lazima wawe na 2FA. Reset inafanywa na developer kupitia CLI.'; }
        elseif (!et_mfa_consume($id, (string) ($_POST['code'] ?? ''))) {
            et_mfa_failed($id); $error = 'Code si sahihi au imeshatumika.';
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
<section class="form-section" style="max-width:680px"><h2>Authenticator</h2><p>Admins wote wanahitaji 2FA kabla ya kutumia modules nyingine. Owner pekee ana ruhusa ya muda kwenye localhost development.</p>
<?php if (!$state && et_owner_development_exception($user)): ?><p><a class="admin-button secondary" href="index.php">Endelea kwenye Dashboard — localhost development</a></p><?php endif; ?>
<?php if ($error): ?><div class="admin-alert error" role="alert"><?= et_e($error) ?></div><?php endif; ?>
<?php if ($lockRemaining > 0): ?><div class="admin-alert" data-mfa-countdown="<?= $lockRemaining ?>">Jaribu tena baada ya <span data-mfa-time><?= sprintf('%02d:%02d', intdiv($lockRemaining, 60), $lockRemaining % 60) ?></span>.</div><?php endif; ?>
<?php if ($codes): ?><div class="admin-alert"><h3>Hifadhi recovery codes sasa</h3><p>Zinaonyeshwa mara hii tu. Kila code itumike mara moja. Hifadhi sehemu salama nje ya simu yako. Faili linalopakuliwa halina encryption; usilishiriki.</p><pre id="recoveryCodes"><?= et_e(implode("\n", $codes)) ?></pre><button type="button" class="admin-button secondary" data-download-recovery>Download backup codes (.txt)</button><span data-recovery-download-status role="status"></span></div><?php endif; ?>
<?php if ($state): ?><p><strong>2FA imewashwa.</strong></p><form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><div class="form-field"><label>Nenosiri la sasa<input name="password" type="password" autocomplete="current-password" required></label></div><div class="form-field"><label>Authenticator / recovery code<input name="code" autocomplete="one-time-code" maxlength="23" required></label></div><button class="admin-button" name="action" value="regenerate">Tengeneza recovery codes mpya</button></form><p><a href="index.php">Endelea kwenye Dashboard</a></p>
<?php else: ?>
<?php if ($setup): ?><p>Scan QR ndani ya Authenticator. Usishiriki QR au secret.</p><img src="<?= et_e(et_totp()->getQRCodeImageAsDataUri('ElimuTaifa:' . $user['username'], $setup['secret'], 240)) ?>" width="240" height="240" alt="QR ya kusetup Authenticator"><p>Au ingiza setup key mwenyewe: <code><?= et_e($setup['secret']) ?></code></p><?php endif; ?>
<form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="action" value="<?= $setup ? 'enable' : 'start' ?>"><div class="form-field"><label>Nenosiri la sasa<input name="password" type="password" autocomplete="current-password" required></label></div><?php if ($setup): ?><div class="form-field"><label>Code ya tarakimu 6<input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required></label></div><?php endif; ?><button class="admin-button"><?= $setup ? 'Thibitisha na washa 2FA' : 'Anza setup ya 2FA' ?></button></form>
<?php endif; ?></section>
<?php et_admin_footer(); ?>
