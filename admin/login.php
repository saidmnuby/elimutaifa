<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_auth.php';
et_admin_boot();

if (et_admin_user() !== null) {
    et_redirect('index.php');
}

$database = et_db();
$hasAdmin = (int) $database->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() > 0;
$flash = et_take_flash();
$error = $flash && $flash['type'] === 'error' ? (string) $flash['message'] : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!et_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'This request has expired. Reload the page.';
    } else {
        $result = et_attempt_admin_login((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''));
        if ($result['ok']) {
            if (!empty($result['mfa_required'])) { et_redirect('verify.php'); }
            et_redirect('index.php');
        }
        $error = $result['message'];
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login | ElimuTaifa</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/brand/favicon32px.ico">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="login-page">
<main class="login-card">
    <div class="login-brand"><img src="../assets/img/brand/circle_logo.png" alt=""><span><strong>ElimuTaifa</strong><small>Content Manager</small></span></div>
    <h1>Admin sign-in</h1>
    <p>Manage announcements and messages.</p>
    <?php if ($error !== ''): ?><div class="admin-alert error" role="alert"><?= et_e($error) ?></div><?php endif; ?>
    <?php if (!$hasAdmin): ?>
        <div class="admin-alert error">No admin account exists. Use the command in the README to create the first account.</div>
    <?php endif; ?>
    <form method="post" class="admin-form" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>">
        <div class="form-field"><label for="username">Username</label><input id="username" name="username" maxlength="80" autocomplete="username" required autofocus></div>
        <div class="form-field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
        <button class="admin-button" type="submit"<?= !$hasAdmin ? ' disabled' : '' ?>>Sign in</button>
    </form>
    <div class="login-help">Admin pages are hidden from search engines. Use HTTPS on the live site.</div>
</main>
</body>
</html>
