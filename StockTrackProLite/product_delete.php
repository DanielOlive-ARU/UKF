<?php
include 'includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
mysql_query("DELETE FROM products WHERE id=$id");
header('Location: products.php?msg=deleted');
exit();
