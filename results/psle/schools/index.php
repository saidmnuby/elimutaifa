<?php
require_once dirname(__DIR__, 3) . '/includes/page_headers.php';
et_send_nonindex_page_headers();
if(isset($_POST['submit'])){
include 'validationEngine.php'; 
include "header.php";
include "body.php";

}else{
    header("location: ../../psle");
}

?>
