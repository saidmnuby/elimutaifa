<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_auth.php';
et_admin_boot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !et_verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(405);
    exit('Method not allowed');
}

$user = et_admin_user();
if ($user !== null) {
    et_audit((int) $user['id'], 'logout', 'admin_user', (int) $user['id']);
}
et_admin_logout();
et_redirect('login.php');
