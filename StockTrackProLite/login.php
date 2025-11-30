<?php
/**
 * Handles office login submissions.
 * Authenticates against the legacy `users` table via the PDO helper.
 */
session_start();
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/login_throttle.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'stock_login')) {
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
            $user = Database::fetchOne(
                "SELECT id, username, role
                 FROM users
                 WHERE username = :username AND password = :password
                 LIMIT 1",
                array(
                    ':username' => $username,
                    // TODO(§Language compatibility sweep, LegacyBusinessCase.docx): Migrate to password_hash()/password_verify() during PHP 8 transition. MD5 is insecure and preserved here only for legacy compatibility.
                    ':password' => md5($password)
                )
            );

            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user']    = $user['username'];
                $_SESSION['role']    = $user['role'];
                LoginThrottle::clear($throttleKey);

                header('Location: dashboard.php');
                exit();
            }
        } catch (Exception $exception) {
            // Optional logging hook; fall through to the error flag.
        }
    }

    LoginThrottle::registerFailure($throttleKey);
    header('Location: index.php?error=1');
    exit();
}

header('Location: index.php');
exit();
?>
