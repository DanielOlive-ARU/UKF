<?php
// Test bypass hook to avoid redirects during automated tests.
if (defined('TEST_BYPASS_AUTH') && TEST_BYPASS_AUTH) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

if (empty($_SESSION['wh_user']) || empty($_SESSION['wh_user_id']) || !array_key_exists('wh_role', $_SESSION)) {
	header('Location: index.php');
	exit();
}
?>
