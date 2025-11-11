<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* legacy MD5 password */
    $u = mysql_real_escape_string($_POST['username']);
    $p = md5($_POST['password']);

    $res = mysql_query("
        SELECT id, username, role
        FROM wh_users
        WHERE username='$u' AND password='$p'
        LIMIT 1
    ");

    if ($row = mysql_fetch_assoc($res)) {
        /* valid login — store details */
        $_SESSION['wh_user_id'] = $row['id'];
        $_SESSION['wh_user']    = $row['username'];
        $_SESSION['wh_role']    = $row['role'];

        header('Location: dashboard.php');
        exit();
    }
}

/* failed login — back to index with error flag */
header('Location: index.php?error=1');
exit();
?>
