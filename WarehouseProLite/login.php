<?php
session_start();
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/login_throttle.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'wh_login')) {
        header('Location: index.php?error=csrf');
        exit();
    }

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $throttleKey = LoginThrottle::makeKey($username);

    if (LoginThrottle::isLocked($throttleKey)) {
        header('Location: index.php?error=locked');
        exit();
    }

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
                session_regenerate_id(true);
                $_SESSION['wh_user_id'] = $row['id'];
                $_SESSION['wh_user']    = $row['username'];
                $_SESSION['wh_role']    = $row['role'];
                LoginThrottle::clear($throttleKey);

                header('Location: dashboard.php');
                exit();
            }
        } catch (Exception $exception) {
            // fall through to error flag; optional logging could go here
        }
    }
    LoginThrottle::registerFailure($throttleKey);
    header('Location: index.php?error=1');
    exit();
}

header('Location: index.php');
exit();
?>
