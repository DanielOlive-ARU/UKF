<?php
/* customer_delete.php – Simple delete + redirect */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once 'includes/auth.php';
require_once dirname(__DIR__) . '/includes/security.php';

$id = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) ? (int)$_POST['id'] : 0;
$token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
$redirect = 'error';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($token, 'stock_customer_delete')) {
    header('Location: customers.php?msg=csrf');
    exit();
}

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
