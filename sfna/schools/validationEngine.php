<?php
require_once dirname(__DIR__, 2) . '/includes/session.php';
grf_start_session();

require_once dirname(__DIR__, 2) . '/includes/district_directory.php';
include_once dirname(__DIR__, 2) . '/includes/result_request.php';

$schools = []; // Array to store extracted school objects

$examYear  = isset($_POST['finalexamYear']) ? filter_var($_POST['finalexamYear'], FILTER_VALIDATE_INT) : null;
$regiontz  = isset($_POST['region']) ? trim($_POST['region']) : '';
$districtz = isset($_POST['municipality']) ? trim($_POST['municipality']) : '';
$searchKey = $districtz;


$code = grf_district_code_for_selection($regiontz, $districtz);

if ($examYear === null || $examYear < 2010 || $examYear > 2026 || $code === null) {
    $_SESSION['error_title'] = 'Error_<V001>';
    $_SESSION['error_message'] = 'Chagua mwaka na halmashauri halali kisha ujaribu tena.';
    $_SESSION['style'] = 'failed-alert';
    header('Location: ../error/');
    exit();
}

if ($examYear > 2023) {
    $url = "https://onlinesys.necta.go.tz/results/$examYear/sfna/results/distr_ps$code.htm";
} elseif ($examYear !== null && $examYear <= 2023) {
    $url = "https://maktaba.tetea.org/exam-results/SFNA$examYear/distr_ps$code.htm";
}


?>
