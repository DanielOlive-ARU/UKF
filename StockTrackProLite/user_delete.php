<?php
/**
 * user_delete.php - Delete office user (admin only, POST-only)
 */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once 'includes/auth.php';
require_once dirname(__DIR__) . '/includes/security.php';

/* Admin-only access */
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php?msg=denied');
    exit();
}

$id = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) ? (int)$_POST['id'] : 0;

/* CSRF check */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'stock_user_delete')) {
    header('Location: users.php?msg=csrf');
    exit();
}

/* Prevent self-deletion */
if ($id === (int)$_SESSION['user_id']) {
    header('Location: users.php?msg=self');
    exit();
}

/* Execute delete */
if ($id > 0) {
    try {
        Database::query('DELETE FROM users WHERE id = :id', array(':id' => $id));
        $redirect = 'deleted';
    } catch (PDOException $e) {
        $redirect = 'error';
    }
} else {
    $redirect = 'error';
}

header('Location: users.php?msg=' . $redirect);
exit();
