<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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
