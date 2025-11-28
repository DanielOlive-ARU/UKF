<?php
session_start();
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username !== '' && $password !== '') {
        try {
            $row = Database::fetchOne(
                "SELECT id, username, role
                 FROM wh_users
                 WHERE username = :username AND password = :password
                 LIMIT 1",
                array(
                    ':username' => $username,
                    ':password' => md5($password) // legacy hash retained
                )
            );

            if ($row) {
                $_SESSION['wh_user_id'] = $row['id'];
                $_SESSION['wh_user']    = $row['username'];
                $_SESSION['wh_role']    = $row['role'];

                header('Location: dashboard.php');
                exit();
            }
        } catch (Exception $exception) {
            // fall through to error flag; optional logging could go here
        }
    }
}

header('Location: index.php?error=1');
exit();
?>
