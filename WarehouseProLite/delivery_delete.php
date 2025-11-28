<?php
/* delivery_delete.php – remove a goods-in record + reverse stock */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/auth.php';   // ensure user is logged in

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = 'error';

if ($id) {
    try {
        Database::transaction(function () use ($id) {
            $row = Database::fetchOne(
                "SELECT product_id, qty FROM deliveries WHERE id = :id FOR UPDATE",
                array(':id' => $id)
            );

            if (!$row) {
                throw new RuntimeException('Delivery not found.');
            }

            Database::query(
                "UPDATE products SET stock = stock - :qty WHERE id = :id",
                array(':qty' => $row['qty'], ':id' => $row['product_id'])
            );

            Database::query('DELETE FROM deliveries WHERE id = :id', array(':id' => $id));
        });

        $msg = 'deleted';
    } catch (RuntimeException $runtimeException) {
        $msg = 'error';
    } catch (Exception $exception) {
        $msg = 'error';
    }
}

header('Location: deliveries.php?msg=' . $msg);
exit();
?>
