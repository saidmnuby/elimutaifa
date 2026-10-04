<?php
declare(strict_types=1);
require_once __DIR__ . '/secondary_directory.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { http_response_code(405); exit; }
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
$query = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
if (!is_int($year) || !grf_is_valid_exam_year($year) || mb_strlen($query) > 80) { http_response_code(422); echo '{"ok":false}'; exit; }
try {
    $items = et_secondary_directory_search($level, $year, $query);
    echo json_encode(['ok'=>true, 'items'=>$items], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $exception) {
    et_record_system_event('school_directory_unavailable', 'School directory could not be prepared.', 'warning', ['exam_type'=>strtoupper($level)]);
    http_response_code(503); echo '{"ok":false}';
}
