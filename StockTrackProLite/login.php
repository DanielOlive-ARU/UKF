<?php
/**
 * Handles office login submissions.
 * Authenticates against the legacy `users` table via the PDO helper.
 */
session_start();
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username !== '' && $password !== '') {
        try {
            $user = Database::fetchOne(
                "SELECT id, username, role
                 FROM users
                 WHERE username = :username AND password = :password
                 LIMIT 1",
                array(
                    ':username' => $username,
                    ':password' => md5($password)
                )
            );

            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user']    = $user['username'];
                $_SESSION['role']    = $user['role'];

                header('Location: dashboard.php');
                exit();
            }
        } catch (Exception $exception) {
            // Optional logging hook; fall through to the error flag.
        }
    }

    header('Location: index.php?error=1');
    exit();
}

header('Location: index.php');
exit();
?>
