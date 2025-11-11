<?php
include 'includes/db.php';
include 'includes/auth.php';      // ensure only logged users can delete

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    mysql_query("DELETE FROM adjustments WHERE id=$id");
}
header('Location: adjustments.php?msg=deleted');
exit();
