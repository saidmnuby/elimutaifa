<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/content.php';
require_once dirname(__DIR__) . '/includes/monitoring.php';
et_register_fatal_error_monitoring();
$apiScriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/api/announcements.php'));
$apiMarkerPosition = strpos($apiScriptName, '/api/announcements.php');
$apiBasePath = $apiMarkerPosition === false ? '' : substr($apiScriptName, 0, $apiMarkerPosition);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

try {
    $items = et_public_content(et_db(), 6);
    $payload = [];
    $popup = null;
    foreach ($items as $item) {
        $entry = [
            'id' => (int) $item['id'],
            'category' => $item['category'],
            'category_label' => ET_CONTENT_CATEGORIES[$item['category']] ?? 'Taarifa',
            'title' => $item['title'],
            'excerpt' => $item['excerpt'],
            'media_type' => $item['media_type'],
            'media_url' => et_content_image_url($item, $apiBasePath),
            'audience' => ET_CONTENT_AUDIENCES[$item['audience']] ?? 'Wote',
            'href' => et_content_href($item),
            'external' => $item['destination_type'] === 'external',
            'published_at' => $item['published_at'],
            'updated_at' => $item['updated_at'],
            'is_featured' => (bool) $item['is_featured'],
        ];
        $payload[] = $entry;
        if ($popup === null && (int) $item['is_popup'] === 1) {
            $popup = $entry;
        }
    }
    echo json_encode(['ok' => true, 'items' => $payload, 'popup' => $popup], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    et_record_system_event('announcement_api_error', 'The public announcement feed could not be generated.', 'error', ['error_code' => get_class($exception)]);
    http_response_code(503);
    echo json_encode(['ok' => false, 'items' => [], 'popup' => null]);
}
