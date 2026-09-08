<?php
session_start();
if (isset($_SESSION['error_message'])) {
    $error_title = $_SESSION['error_title'];
    $style = isset($_SESSION['style']) ? $_SESSION['style'] : 'failed-alert';
        $error = $_SESSION['error_message'];
        $nectaStatement = isset($_SESSION['nectaStatement']) ? $_SESSION['nectaStatement'] : '';
        $necta = isset($_SESSION['NECTA']) ? $_SESSION['NECTA'] : '';
        
        unset($_SESSION['error_message'], $_SESSION['style'], $_SESSION['NECTA'], $_SESSION['nectaStatement']);


} else {
    // If the error message is not set, redirect to the home page or another appropriate page
    header("Location: ../");
    exit();
}

?>