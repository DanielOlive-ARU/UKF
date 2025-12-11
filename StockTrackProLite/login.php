<?php
/**
 * Handles office login submissions.
 * Authenticates against the `users` table via the PDO helper.
 * Supports opportunistic rehashing from legacy MD5 to bcrypt.
 */
session_start();
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/login_throttle.php';
require_once dirname(__DIR__) . '/includes/password.php';

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
            // Fetch user by username only - verify password in PHP
            $user = Database::fetchOne(
                "SELECT id, username, password, role
                 FROM users
                 WHERE username = :username
                 LIMIT 1",
                array(':username' => $username)
            );

            if ($user) {
                $result = verifyPassword($password, $user['password']);

                if ($result['valid']) {
                    // Opportunistic rehash: upgrade MD5 to bcrypt on successful login
                    if ($result['needs_rehash']) {
                        $newHash = hashPassword($password);
                        Database::query(
                            "UPDATE users SET password = :password WHERE id = :id",
                            array(':password' => $newHash, ':id' => $user['id'])
                        );
                    }

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user']    = $user['username'];
                    $_SESSION['role']    = $user['role'];
                    LoginThrottle::clear($throttleKey);

                    header('Location: dashboard.php');
                    exit();
                }
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
