<?php
/**
 * Handles warehouse login submissions.
 * Authenticates against the `wh_users` table via the PDO helper.
 * Supports opportunistic rehashing from legacy MD5 to bcrypt.
 */
session_start();
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/login_throttle.php';
require_once dirname(__DIR__) . '/includes/password.php';

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
            // Fetch user by username only - verify password in PHP
            $row = Database::fetchOne(
                "SELECT id, username, password, role
                 FROM wh_users
                 WHERE username = :username
                 LIMIT 1",
                array(':username' => $username)
            );

            if ($row) {
                $result = verifyPassword($password, $row['password']);

                if ($result['valid']) {
                    // Opportunistic rehash: upgrade MD5 to bcrypt on successful login
                    if ($result['needs_rehash']) {
                        $newHash = hashPassword($password);
                        Database::query(
                            "UPDATE wh_users SET password = :password WHERE id = :id",
                            array(':password' => $newHash, ':id' => $row['id'])
                        );
                    }

                    session_regenerate_id(true);
                    $_SESSION['wh_user_id'] = $row['id'];
                    $_SESSION['wh_user']    = $row['username'];
                    $_SESSION['wh_role']    = $row['role'];
                    LoginThrottle::clear($throttleKey);

                    header('Location: dashboard.php');
                    exit();
                }
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
