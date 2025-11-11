<?php
/* qa_delete.php – remove a QA sample */
include 'includes/db.php';
include 'includes/auth.php';   // ensure user is logged in

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    mysql_query("DELETE FROM qa_samples WHERE id = $id");
}

header('Location: qa_samples.php?msg=deleted');
exit();
?>
