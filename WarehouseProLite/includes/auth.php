<?php
session_start();
if(!isset($_SESSION['wh_user'])){
 header('Location: index.php');exit();
}
?>