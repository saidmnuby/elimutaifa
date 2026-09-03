<?php
include 'validAcsee.php'; // Include the file with valid advanced school IDs

// Retrieve values from GET parameters
$examLevel = isset($_POST['exam_level']) ? filter_var(trim($_POST['exam_level']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : 'acsee';
$examYear  = isset($_POST['exam_year']) ? filter_var(trim($_POST['exam_year']), FILTER_SANITIZE_NUMBER_INT) : '2025';
$candidate = isset($_POST['candidate']) ? filter_var(trim($_POST['candidate']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';



$parts = explode('/', $candidate);
$school_id = strtoupper(trim($parts[0]));
$schoolCode = strtolower(trim($parts[0]));

// Validate NECTA format server-side
$nectaPattern = '/^(?:[SPE]Q?\d{4}\/\d{4}|PS\d{6,7}-\d{3,4})$/i';

if (!preg_match($nectaPattern, $candidate)) {
    // Handle invalid candidate number output
    session_start();
    $_SESSION['error_message'] = "namba ya mtihani au kidato siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena.";
    $_SESSION['style'] = "failed-alert";
        header("Location: ../error/");
        exit();
}

// Proceed with database query or web scraping using $examLevel, $examYear, $candidate
if ($examLevel === 'csee' && preg_match('/^[SP]/i', trim($candidate))) {
    if($examYear === '2026' || $examYear === '2025' || $examYear === '2024' || $examYear === '2023'){

        $url = "https://onlinesys.necta.go.tz/results/$examYear/$examLevel/results/$schoolCode.htm";

    }
    
} else {
 

if ($examLevel === 'acsee' && in_array($school_id, $valid_ids, true)) {
    if($examYear === '2023' || $examYear === '2024' || $examYear === '2025' ){

        $url = "https://onlinesys.necta.go.tz/results/$examYear/$examLevel/results/$schoolCode.htm";
        
    }elseif($examYear === '2026'){
        $url = "https://matokeo.necta.go.tz/results/$examYear/$examLevel/results/$schoolCode.htm";
    }
} else {
    session_start();
    $_SESSION['error_message'] = "namba ya mtihani au kidato siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena.";
    $_SESSION['style'] = "failed-alert";
        header("Location: ../error/");
        exit();
}
  

}

?>