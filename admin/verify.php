<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_auth.php';
et_admin_boot();
$pending = $_SESSION['et_mfa_pending'] ?? null;
if (!is_array($pending) || !et_mfa_pending_valid($pending)) {
    unset($_SESSION['et_mfa_pending']);
    et_redirect('login.php');
}
$id = (int) $pending['id'];
$statement = et_db()->prepare('SELECT * FROM admin_users WHERE id=? AND is_active=1 AND deleted_at IS NULL');
$statement->execute([$id]);
$user = $statement->fetch();
$state = et_mfa_state($id);
if (!$user || !$state || !hash_equals($pending['secret_hash'], hash('sha256', $state['secret']))) {
    et_admin_logout(); et_redirect('login.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!et_verify_csrf($_POST['csrf_token'] ?? null)) { $error = 'Pakia ukurasa upya.'; }
    elseif (!et_mfa_rate_allowed($id)) { $error = 'Majaribio mengi. Lock ya 2FA ni dakika 5; angalia muda uliobaki hapa chini.'; }
    elseif (et_mfa_consume($id, (string) ($_POST['code'] ?? ''))) {
        et_complete_admin_login($user, hash('sha256', $state['secret']));
        et_audit($id, 'two_factor_verified', 'admin_user', $id);
        et_redirect('index.php');
    } else {
        et_mfa_failed($id);
        if (et_mfa_pending_failure($id)) {
            et_flash('error', 'Majaribio 3 ya code yameshindwa. Kikao cha verification kimefungwa; ingia tena kwa password.');
            et_redirect('login.php');
        }
        $error = 'Code si sahihi au imeshatumika. Jaribu code mpya.';
    }
}
$lockRemaining = et_mfa_lock_remaining($id);
?>
<!doctype html><html lang="sw"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>2FA | ElimuTaifa Admin</title><link rel="stylesheet" href="assets/admin.css"><link rel="icon" href="../assets/img/brand/favicon32px.ico"></head>
<body class="login-page"><main class="login-card"><h1>Thibitisha kuingia</h1><p>Weka code ya Authenticator au recovery code. Hatua hii inaisha baada ya dakika 5.</p>
<?php if ($error): ?><div class="admin-alert error" role="alert"><?= et_e($error) ?></div><?php endif; ?>
<?php if ($lockRemaining > 0): ?><div class="admin-alert" data-mfa-countdown="<?= $lockRemaining ?>">Jaribu tena baada ya <span data-mfa-time><?= sprintf('%02d:%02d', intdiv($lockRemaining, 60), $lockRemaining % 60) ?></span>.</div><?php endif; ?>
<script src="assets/admin.js" defer></script>
<form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><div class="form-field"><label for="code">Authenticator / recovery code</label><input id="code" name="code" autocomplete="one-time-code" maxlength="23" required autofocus></div><button class="admin-button">Thibitisha</button></form><a href="login.php">Rudi kwenye login</a></main></body></html>
