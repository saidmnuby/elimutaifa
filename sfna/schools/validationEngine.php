<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "district.php";
include_once dirname(__DIR__, 2) . '/includes/result_request.php';

$schools = []; // Array to store extracted school objects

$examYear  = isset($_POST['finalexamYear']) ? filter_var($_POST['finalexamYear'], FILTER_VALIDATE_INT) : null;
$regiontz  = isset($_POST['region']) ? trim($_POST['region']) : '';
$districtz = isset($_POST['municipality']) ? trim($_POST['municipality']) : '';


// Create a lookup array with lowercase keys for case-insensitive matching
$normalizedMap = array_change_key_case($municipalitiesByRegion ?? [], CASE_LOWER);
$searchKey     = mb_strtolower($districtz, 'UTF-8');

// Lookup using the normalized key
$code = $normalizedMap[$searchKey] ?? 'N/A';

if ($examYear > 2023) {
    $url = "https://onlinesys.necta.go.tz/results/$examYear/sfna/results/distr_ps$code.htm";
} elseif ($examYear !== null && $examYear <= 2023) {
    $url = "https://maktaba.tetea.org/exam-results/SFNA$examYear/distr_ps$code.htm";
}


?>