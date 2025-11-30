<?php
/* qa_delete.php – remove a QA sample */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/auth.php';   // ensure user is logged in
require_once dirname(__DIR__) . '/includes/security.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = 'deleted';

if (!Csrf::validate(isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '', 'wh_qa_delete')) {
    header('Location: qa_samples.php?msg=csrf');
    exit();
}

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
