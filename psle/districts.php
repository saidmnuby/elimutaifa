<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/district_options.php';
header('Content-Type: application/json; charset=utf-8');

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: 2025;
$region = strtolower(trim((string) ($_GET['region'] ?? '')));
echo json_encode(['districts' => grf_district_options('psle', $year, $region)], JSON_UNESCAPED_UNICODE);
