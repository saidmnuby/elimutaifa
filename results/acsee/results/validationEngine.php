<?php

require_once dirname(__DIR__, 3) . '/includes/session.php';
require_once dirname(__DIR__, 3) . '/includes/validation.php';
require_once dirname(__DIR__, 3) . '/includes/exam_cycles.php';
grf_start_session();

$examYear = isset($_POST['examYear']) ? filter_var($_POST['examYear'], FILTER_VALIDATE_INT) : false;
$examLevel = 'acsee';
$candidate = isset($_POST['candidate']) ? filter_var(trim($_POST['candidate']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';



$parts = explode('/', $candidate);
$schoolCode = strtolower(trim($parts[0]));


// Validate NECTA format server-side
if (!grf_is_valid_secondary_candidate($candidate) || !grf_is_valid_exam_year($examYear)) {
    // Handle invalid candidate number output
    $_SESSION['error_title'] = "Errorr_<V001>";
    $_SESSION['error_message'] = "namba ya mtihani au mwaka siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena.";
    $_SESSION['style'] = "failed-alert";
        header("Location: ../error/");
        exit();
}

try {
    $url = et_exam_source_url('acsee', $examYear, 'school', $school_id ?? $schoolCode);
} catch (Throwable $exception) {
    $_SESSION['error_title'] = 'Source unavailable';
    $_SESSION['error_message'] = 'Chanzo cha matokeo ya mwaka huu hakipatikani kwa sasa. Tafadhali jaribu baadaye.';
    $_SESSION['style'] = 'warning-alert';
    header('Location: ../error/');
    exit;
}
