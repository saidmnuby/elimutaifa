<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/admin_auth.php';
require_once dirname(__DIR__, 2) . '/includes/content.php';
et_admin_boot();
$user = et_require_admin();
et_require_owner($user);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed');
}
if (!et_verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid request');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$database = et_db();
$statement = $database->prepare('SELECT title,status,media_url FROM content_items WHERE id=:id LIMIT 1');
$statement->execute(['id' => $id]);
$item = $statement->fetch();
if (!$item) {
    et_flash('error', 'Content not found.');
    et_redirect('./');
}
if ($item['status'] !== 'archived') {
    et_flash('error', 'Archive this content before deleting it permanently.');
    et_redirect('./');
}

try {
    $database->beginTransaction();
    $deleteStatement = $database->prepare("DELETE FROM content_items WHERE id=:id AND status='archived'");
    $deleteStatement->execute(['id' => $id]);
    if ($deleteStatement->rowCount() !== 1) {
        throw new RuntimeException('Content deletion did not affect one record.');
    }
    et_audit((int) $user['id'], 'content_deleted', 'content_item', $id, (string) $item['title']);
    $database->commit();
} catch (Throwable $exception) {
    if ($database->inTransaction()) {
        $database->rollBack();
    }
    et_flash('error', 'Could not permanently delete the content.');
    et_redirect('./');
}

$mediaDeleted = et_delete_managed_content_image((string) $item['media_url']);
$sitemapUpdated = et_rebuild_sitemap($database);
et_flash($sitemapUpdated && $mediaDeleted ? 'success' : 'error', $sitemapUpdated && $mediaDeleted
    ? 'Content permanently deleted.'
    : 'Content deleted, but its media or sitemap needs checking.');
et_redirect('./');
