<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once 'includes/auth.php';
require_once dirname(__DIR__) . '/includes/security.php';

$id = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) ? (int)$_POST['id'] : 0;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
	!Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'stock_product_delete')) {
	header('Location: products.php?msg=csrf');
	exit();
}
if ($id > 0) {
	try {
		Database::query('DELETE FROM products WHERE id = :id', array(':id' => $id));
		$redirectMsg = 'deleted';
	} catch (PDOException $exception) {
		// TODO: introduce a soft delete workflow so FK-protected rows can be retired without data loss.
		if ($exception->getCode() === '23000') {
			$redirectMsg = 'in_use';
		} else {
			$redirectMsg = 'error';
		}
	}
} else {
	$redirectMsg = 'error';
}

header('Location: products.php?msg=' . $redirectMsg);
exit();
