<?php
// login.php  (place in StockTrackProLite root)

session_start();

// ---  VERY LEGACY / UNSAFE  ---
// Accept *any* credentials and mark the user as logged-in.
// Replace this with a real lookup + hashing.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['user'] = isset($_POST['username']) ? $_POST['username'] : 'demo';
    $_SESSION['role'] = 'admin';           // default for now
    header('Location: dashboard.php');
    exit();
}

// If someone hit /login.php directly (GET) show the login form again.
header('Location: index.php');
