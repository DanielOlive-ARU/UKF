<?php
/* customer_delete.php – Simple delete + redirect */
include 'includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

mysql_query("DELETE FROM customers WHERE id=$id");
header('Location: customers.php?msg=deleted');
exit();
