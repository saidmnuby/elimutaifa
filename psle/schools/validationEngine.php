<?php
// Ensure session is started cleanly at the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Retrieve values from POST parameters
$examYear = isset($_POST['finalexamYear']) ? filter_var($_POST['finalexamYear'], FILTER_VALIDATE_INT) : false;
$regiontz = isset($_POST['municipality']) ? filter_var(trim($_POST['candidate']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
$districtz = isset($_POST['municipality']) ? filter_var(trim($_POST['candidate']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';



if (!preg_match($nectaPattern, $candidate)) {
    if (!preg_match($nectaPb, $candidate)) {
    $_SESSION['error_title'] = "Errorr_<V001>";
    $_SESSION['error_message'] = "namba ya mtihani au mwaka siyo sahihi. Tafadhali hakiki namba, mwaka kisha ujaribu tena.";
    $_SESSION['style'] = "failed-alert";
    header("Location: ../error/");
    exit();
    }
}

// Proceed with URL generation

    if ($examYear >= 2023) {
        $url = "https://onlinesys.necta.go.tz/results/$examYear/psle/results/shl_$schoolCode.htm";

    }elseif($examYear <= 2022){
        $url = "https://maktaba.tetea.org/exam-results/PSLE$examYear/shl_$schoolCode.htm";

    }else {
        $_SESSION['error_title'] = "Errorr_<V002>";
        $_SESSION['error_message'] = "namba ya mtihani au mwaka siyo sahihi. Tafadhali hakiki namba, mwaka kisha ujaribu tena.";
        $_SESSION['style'] = "failed-alert";
        header("Location: ../error/");
        exit();
}

?>