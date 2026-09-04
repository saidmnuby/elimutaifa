<?php
// Ensure session is started cleanly at the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Retrieve values from POST parameters
$examYear  = isset($_POST['examYear']) ? filter_var($_POST['examYear'], FILTER_VALIDATE_INT) : false;
$candidate = isset($_POST['candidate']) ? filter_var(trim($_POST['candidate']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';

$parts = explode('-', $candidate);
$school_id = strtoupper(trim($parts[0]));
$schoolCode = strtolower(trim($parts[0]));

// Validate NECTA format server-side
$nectaPattern = '/^(PS\d{7}-\d{4})$/i';
$nectaPb = '/^(PS\d{7}-\d{3})$/i';

if (!preg_match($nectaPattern, $candidate)) {
    if (!preg_match($nectaPb, $candidate)) {
    $_SESSION['error_message'] = "namba ya mtihani au kidato siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena1.";
    $_SESSION['style'] = "failed-alert";
    header("Location: ../error/");
    exit();
    }
}

// Proceed with URL generation

    if ($examYear >= 2024) {
        $url = "https://onlinesys.necta.go.tz/results/$examYear/sfna/results/$schoolCode.htm";

    }elseif($examYear <= 2023){
        $url = "https://maktaba.tetea.org/exam-results/SFNA$examYear/$schoolCode.htm";

    }else {
        $_SESSION['error_message'] = "namba ya mtihani au kidato siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena2.";
        $_SESSION['style'] = "failed-alert";
        header("Location: ../error/");
        exit();
}

?>