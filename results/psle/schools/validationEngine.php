<?php
require_once dirname(__DIR__, 3) . '/includes/session.php';
grf_start_session();

require_once dirname(__DIR__, 3) . '/includes/district_directory.php';
require_once dirname(__DIR__, 3) . '/includes/validation.php';
require_once dirname(__DIR__, 3) . '/includes/exam_cycles.php';
include_once dirname(__DIR__, 3) . '/includes/result_request.php';

$schools = []; // Array to store extracted school objects

$examYear  = isset($_POST['finalexamYear']) ? filter_var($_POST['finalexamYear'], FILTER_VALIDATE_INT) : null;
$regiontz  = isset($_POST['region']) ? trim($_POST['region']) : '';
$districtz = isset($_POST['municipality']) ? trim($_POST['municipality']) : '';
$searchKey = $districtz;


$code = grf_district_code_for_selection($regiontz, $districtz);

if (!grf_is_valid_exam_year($examYear) || $code === null) {
    $_SESSION['error_title'] = 'Error_<V001>';
    $_SESSION['error_message'] = 'Tafadhali chagua mwaka wa matokeo na halmashauri kutoka kwenye orodha, kisha ujaribu tena.';
    $_SESSION['style'] = 'failed-alert';
    header('Location: ../error/');
    exit();
}

try {
    $url = et_exam_source_url('psle', $examYear, 'directory', $code);
} catch (Throwable $exception) {
    $_SESSION['error_title'] = 'Source unavailable';
    $_SESSION['error_message'] = 'Chanzo cha matokeo ya mwaka huu hakipatikani kwa sasa. Tafadhali jaribu baadaye.';
    $_SESSION['style'] = 'warning-alert';
    header('Location: ../error/');
    exit;
}
