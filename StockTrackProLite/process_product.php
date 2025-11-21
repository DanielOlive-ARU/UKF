<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$name = trim($_POST['name']);
	$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;

	if ($name !== '') {
		Database::query(
			"INSERT INTO products (name, price) VALUES (:name, :price)",
			array(':name' => $name, ':price' => $price)
		);
		header('Location: products.php?msg=added');
		exit();
	}
}

header('Location: products.php?msg=error');
exit();
?>
