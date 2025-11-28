<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/auth.php';      // ensure only logged users can delete

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = 'error';

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
