<?php
/* delivery_edit.php – update an existing delivery */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$notice = '';

/* ---------- Update on POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pidNew = (int)$_POST['product_id'];
    $qtyNew = (int)$_POST['qty'];
    $refNew = trim($_POST['ref']);

    try {
        Database::transaction(function () use ($id, $pidNew, $qtyNew, $refNew) {
            $old = Database::fetchOne(
                "SELECT product_id, qty FROM deliveries WHERE id = :id FOR UPDATE",
                array(':id' => $id)
            );

            if (!$old) {
                throw new RuntimeException('Delivery not found.');
            }

            Database::query(
                "UPDATE deliveries
                 SET product_id = :product_id,
                     qty = :qty,
                     supplier_ref = :ref
                 WHERE id = :id",
                array(
                    ':product_id' => $pidNew,
                    ':qty' => $qtyNew,
                    ':ref' => $refNew,
                    ':id' => $id
                )
            );

            if ($pidNew == $old['product_id']) {
                $delta = $qtyNew - $old['qty'];
                if ($delta !== 0) {
                    Database::query(
                        "UPDATE products SET stock = stock + :delta WHERE id = :id",
                        array(':delta' => $delta, ':id' => $pidNew)
                    );
                }
            } else {
                Database::query(
                    "UPDATE products SET stock = stock - :qty WHERE id = :id",
                    array(':qty' => $old['qty'], ':id' => $old['product_id'])
                );
                Database::query(
                    "UPDATE products SET stock = stock + :qty WHERE id = :id",
                    array(':qty' => $qtyNew, ':id' => $pidNew)
                );
            }
        });

        header('Location: deliveries.php?msg=updated');
        exit();
    } catch (RuntimeException $runtimeException) {
        $notice = $runtimeException->getMessage();
    } catch (Exception $exception) {
        $notice = 'Delivery could not be updated. Please try again.';
    }
}

/* ---------- Load existing row ---------- */
$row = Database::fetchOne(
    "SELECT d.*, p.sku, p.name
     FROM deliveries d
     JOIN products p ON p.id = d.product_id
     WHERE d.id = :id",
    array(':id' => $id)
);
if (!$row) {
    echo "<p class='notice'>Delivery not found.</p>";
    include 'includes/footer.php';
    exit();
}

/* Products for dropdown */
$prods = Database::query("SELECT id, sku, name FROM products ORDER BY name")->fetchAll();
?>
<h2>Edit Delivery #<?php echo $id; ?></h2>

<?php if (!empty($notice)): ?>
    <p class="notice"><?php echo htmlspecialchars($notice); ?></p>
<?php endif; ?>

<form action="delivery_edit.php?id=<?php echo $id; ?>" method="post">
    <label>Product
        <select name="product_id" required>
            <?php foreach ($prods as $p): ?>
                <option value="<?php echo $p['id']; ?>"
                    <?php if ($p['id'] == $row['product_id']) echo 'selected'; ?>>
                    <?php echo $p['sku'].' - '.htmlspecialchars($p['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Quantity
        <input type="number" name="qty" min="1"
               value="<?php echo $row['qty']; ?>" required>
    </label>

    <label>Supplier Ref
        <input type="text" name="ref"
               value="<?php echo htmlspecialchars($row['supplier_ref']); ?>">
    </label>

    <p>
        <input type="submit" value="Save Changes">
        <a href="deliveries.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
