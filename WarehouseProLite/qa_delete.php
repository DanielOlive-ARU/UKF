<?php
/* qa_delete.php – remove a QA sample */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/auth.php';   // ensure user is logged in
require_once dirname(__DIR__) . '/includes/security.php';

$id = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) ? (int)$_POST['id'] : 0;
$msg = 'deleted';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'wh_qa_delete')) {
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
