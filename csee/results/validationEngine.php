<?php
session_start();
$examYear = isset($_POST['examYear']) ? filter_var($_POST['examYear'], FILTER_VALIDATE_INT) : false;
$candidate = isset($_POST['candidate']) ? filter_var(trim($_POST['candidate']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';



$parts = explode('/', $candidate);
$school_id = strtoupper(trim($parts[0]));
$schoolCode = strtolower(trim($parts[0]));


// Validate NECTA format server-side
$nectaPattern = '/^(?:[SP]Q?\d{4}\/\d{4})$/i';

if (!preg_match($nectaPattern, $candidate) || $examYear === false || $examYear < 2010 || $examYear > 2026) {
    // Handle invalid candidate number output
    $_SESSION['error_title'] = "Errorr_<V001>";
    $_SESSION['error_message'] = "namba ya mtihani au mwaka siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena.";
    $_SESSION['style'] = "failed-alert";
        header("Location: ../error/");
        exit();
}

$_SESSION['candidate'] = $candidate;
$_SESSION['examYear'] = $examYear;

// candidate number is is less than 500
if (trim($parts[1]) <= '500' ) {
    if($examYear >= '2023'){

        $url = "https://onlinesys.necta.go.tz/results/$examYear/csee/results/$schoolCode.htm";

    }elseif($examYear <= '2022'){
        $url = "https://maktaba.tetea.org/exam-results/CSEE$examYear/$schoolCode.htm";
    }
    
} else {
    $_SESSION['error_title'] = "Errorr_<V002>";
    $_SESSION['error_message'] = "namba ya mtihani au mwaka siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena.";
    $_SESSION['style'] = "failed-alert";
        header("Location: ../error/");
        exit();
}
  
?>