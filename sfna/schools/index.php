<?php
if(isset($_POST['submit'])){
include 'validationEngine.php'; 
include "header.php";
include "body.php";

}else{
    header("location: ../../sfna");
}

?>
