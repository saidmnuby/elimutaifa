<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/admin_auth.php';
require_once dirname(__DIR__, 2) . '/includes/content.php';
et_admin_boot();
$user = et_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !et_verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(405);
    exit('Method not allowed');
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($id <= 0) {
    et_flash('error', 'Maudhui hayajapatikana.');
    et_redirect('./');
}
$database = et_db();
$statement = $database->prepare("UPDATE content_items SET status='archived', is_popup=0, updated_by=:updated_by, updated_at=:updated_at WHERE id=:id");
$statement->execute(['updated_by' => (int) $user['id'], 'updated_at' => et_utc_now(), 'id' => $id]);
et_audit((int) $user['id'], 'content_archived', 'content_item', $id);
et_rebuild_sitemap($database);
et_flash('success', 'Maudhui yamewekwa archive.');
et_redirect('./');
