<?php
/**
 * PHPUnit bootstrap file.
 * Loads the application autoloader and test configuration.
 */

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session for CSRF tests
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Load the shared includes
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/password.php';
require_once dirname(__DIR__) . '/includes/login_throttle.php';
