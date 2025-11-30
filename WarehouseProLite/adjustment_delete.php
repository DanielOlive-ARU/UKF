<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/auth.php';      // ensure only logged users can delete
require_once dirname(__DIR__) . '/includes/security.php';

$id = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) ? (int)$_POST['id'] : 0;
$msg = 'error';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'wh_adjustment_delete')) {
    header('Location: adjustments.php?msg=csrf');
    exit();
}

if ($id) {
    try {
        Database::query('DELETE FROM adjustments WHERE id = :id', array(':id' => $id));
        $msg = 'deleted';
    } catch (Exception $exception) {
        $msg = 'error';
    }
}

header('Location: adjustments.php?msg=' . $msg);
exit();
