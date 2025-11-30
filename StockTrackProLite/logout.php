<?php
/* logout.php – destroy session and return to login */
session_start();
session_regenerate_id(true);
require_once dirname(__DIR__) . '/includes/security.php';
Csrf::reset();

/* Remove all session variables & cookies */
session_unset();
session_destroy();

if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

/* Redirect back to the login page */
header('Location: index.php');
exit();
