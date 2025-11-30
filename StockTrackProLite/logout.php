<?php
/* logout.php – destroy session and return to login */
session_start();

/* Remove all session variables & cookies */
unset($_SESSION['user_id'], $_SESSION['user'], $_SESSION['role']);
session_unset();
session_destroy();

/* Redirect back to the login page */
header('Location: index.php');
exit();
