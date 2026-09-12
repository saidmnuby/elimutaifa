<?php
// Ensure session is started cleanly at the very top
require_once dirname(__DIR__, 2) . '/includes/session.php';
require_once dirname(__DIR__, 2) . '/includes/validation.php';
grf_start_session();

// Retrieve values from POST parameters
$examYear  = isset($_POST['examYear']) ? filter_var($_POST['examYear'], FILTER_VALIDATE_INT) : false;
$candidate = isset($_POST['candidate']) ? filter_var(trim($_POST['candidate']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';

$parts = explode('-', $candidate);
$school_id = strtoupper(trim($parts[0]));
$schoolCode = strtolower(trim($parts[0]));

// Validate NECTA format server-side
if (!grf_is_valid_primary_candidate($candidate) || !grf_is_valid_exam_year($examYear)) {
    $_SESSION['error_title'] = "Errorr_<V001>";
    $_SESSION['error_message'] = "namba ya mtihani au mwaka siyo sahihi. Tafadhali hakiki namba, mwaka kisha ujaribu tena.";
    $_SESSION['style'] = "failed-alert";
    header("Location: ../error/");
    exit();
}

// Proceed with URL generation

    if ($examYear >= 2024) {
        $url = "https://onlinesys.necta.go.tz/results/$examYear/sfna/results/$schoolCode.htm";

    }elseif($examYear <= 2023){
        $url = "https://maktaba.tetea.org/exam-results/SFNA$examYear/$schoolCode.htm";

    }else {
        $_SESSION['error_title'] = "Errorr_<V002>";
        $_SESSION['error_message'] = "namba ya mtihani au mwaka siyo sahihi. Tafadhali hakiki namba, mwaka kisha ujaribu tena.";
        $_SESSION['style'] = "failed-alert";
        header("Location: ../error/");
        exit();
}

?>
