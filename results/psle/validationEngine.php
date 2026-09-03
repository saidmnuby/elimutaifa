<?php
// Ensure session is started cleanly at the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Retrieve values from POST parameters
$examLevel = isset($_POST['exam_level']) ? filter_var(trim($_POST['exam_level']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : 'psle';
$examYear  = isset($_POST['exam_year']) ? filter_var(trim($_POST['exam_year']), FILTER_SANITIZE_NUMBER_INT) : '2025';
$candidate = isset($_POST['candidate']) ? filter_var(trim($_POST['candidate']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';

$parts = explode('-', $candidate);
$school_id = strtoupper(trim($parts[0]));
$schoolCode = strtolower(trim($parts[0]));

// Validate NECTA format server-side
$nectaPattern = '/^(?:[SPE]Q?\d{4}\/\d{4}|PS\d{6,7}-\d{3,4})$/i';

if (!preg_match($nectaPattern, $candidate)) {
    $_SESSION['error_message'] = "namba ya mtihani au kidato siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena1.";
    $_SESSION['style'] = "failed-alert";
    header("Location: ../../error/");
    exit();
}

// Proceed with URL generation
if ($examLevel === 'psle' && preg_match('/^[SP]/i', trim($candidate))) {
    if (in_array($examYear, ['2026', '2025', '2024', '2023'])) {
        $url = "https://onlinesys.necta.go.tz/results/$examYear/$examLevel/results/shl_$schoolCode.htm";
    }
} else {
    $_SESSION['error_message'] = "namba ya mtihani au kidato siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena2.";
    $_SESSION['style'] = "failed-alert";
    header("Location: ../../error/");
    exit();
}

?>