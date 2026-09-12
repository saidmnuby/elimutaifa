<?php

require_once dirname(__DIR__, 2) . '/includes/session.php';
require_once dirname(__DIR__, 2) . '/includes/validation.php';
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

// Candidate numbers use four digits. Do not impose an artificial numeric
// threshold: both school and private-centre sequences must remain searchable.
if ($examYear >= 2023 && $examYear <= 2025) {
    $url = "https://onlinesys.necta.go.tz/results/$examYear/$examLevel/results/$schoolCode.htm";
} elseif ($examYear === 2026) {
    $url = "https://matokeo.necta.go.tz/results/$examYear/$examLevel/results/$schoolCode.htm";
} elseif ($examYear <= 2022) {
    $url = "https://maktaba.tetea.org/exam-results/ACSEE$examYear/$schoolCode.htm";
}
  
if (!isset($url) || $url === '') {
    $_SESSION['error_title'] = "Errorr_<V003>";
    $_SESSION['error_message'] = "namba ya mtihani au mwaka siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena.";
    $_SESSION['style'] = "failed-alert";
    header("Location: ../error/");
    exit();
}

?>
