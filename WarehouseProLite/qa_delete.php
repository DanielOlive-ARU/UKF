<?php
/* qa_delete.php – remove a QA sample */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/auth.php';   // ensure user is logged in

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = 'deleted';

if ($id > 0) {
    try {
        Database::query('DELETE FROM qa_samples WHERE id = :id', array(':id' => $id));
    } catch (Exception $exception) {
        $msg = 'error';
    }
} else {
    $msg = 'error';
}

header('Location: qa_samples.php?msg=' . $msg);
exit();
?>
