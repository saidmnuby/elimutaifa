<?php

include 'validacsee.php';
session_start();

$examYear = isset($_POST['examYear']) ? filter_var($_POST['examYear'], FILTER_VALIDATE_INT) : false;
$examLevel = 'acsee';
$candidate = isset($_POST['candidate']) ? filter_var(trim($_POST['candidate']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';



$parts = explode('/', $candidate);
$school_id = strtoupper(trim($parts[0]));
$schoolCode = strtolower(trim($parts[0]));


// Validate NECTA format server-side
$nectaPattern = '/^(?:[SP]Q?\d{4}\/\d{4})$/i';

if (!preg_match($nectaPattern, $candidate) || $examYear === false || $examYear < 2010 || $examYear > 2026 || !in_array($school_id, $valid_ids, true)) {
    // Handle invalid candidate number output
    $_SESSION['error_message'] = "namba ya mtihani au kidato siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena.";
    $_SESSION['style'] = "failed-alert";
        header("Location: ../error/");
        exit();
}

// candidate number is is less than 500
if (trim($parts[1]) >= '500' && in_array($school_id, $valid_ids, true)) {
    if($examYear >= 2023 && $examYear <= 2025){
        $url = "https://onlinesys.necta.go.tz/results/$examYear/$examLevel/results/$schoolCode.htm";
        
    }elseif($examYear === 2026){
        $url = "https://matokeo.necta.go.tz/results/$examYear/$examLevel/results/$schoolCode.htm";
    
    }elseif($examYear <= 2022){
        $url = "https://maktaba.tetea.org/exam-results/ACSEE$examYear/$schoolCode.htm";
    }
    
} else {
    $_SESSION['error_message'] = "namba ya mtihani au kidato siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena.";
    $_SESSION['style'] = "failed-alert";
        header("Location: ../error/");
        exit();
}
  
if (!isset($url) || $url === '') {
    $_SESSION['error_message'] = "Namba ya mwaka wa mtihani siyo sahihi. Tafadhali chagua mwaka kisha ujaribu tena.";
    $_SESSION['style'] = "failed-alert";
    header("Location: ../error/");
    exit();
}

?>