<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/monitoring.php';

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit;
}
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 4096) {
    http_response_code(413);
    exit;
}
$fetchSite = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
if ($fetchSite !== '' && !in_array($fetchSite, ['same-origin', 'none'], true)) {
    http_response_code(403);
    exit;
}
$input = json_decode(file_get_contents('php://input') ?: '{}', true);
$path = is_array($input) ? (string) ($input['path'] ?? '') : '';
if ($path === '' || !str_starts_with($path, '/') || strlen($path) > 500) {
    http_response_code(422);
    exit;
}
et_record_page_view($path);
http_response_code(204);
