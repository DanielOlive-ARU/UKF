<?php
/**
 * Shared gatekeeper for office routes.
 * Ensures a session exists and the user is authenticated before continuing.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user']) || empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: index.php');
    exit();
}
