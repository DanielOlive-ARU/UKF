<?php
/* customer_delete.php – Simple delete + redirect */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$redirect = 'error';

if ($id > 0) {
	try {
		Database::query('DELETE FROM customers WHERE id = :id', array(':id' => $id));
		$redirect = 'deleted';
	} catch (PDOException $exception) {
		// TODO: implement customer soft delete once order history is surfaced in-app.
		if ($exception->getCode() === '23000') {
			$redirect = 'in_use';
		}
	}
}

header('Location: customers.php?msg=' . $redirect);
exit();
