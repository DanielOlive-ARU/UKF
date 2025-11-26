<?php
/* delivery_add.php – record a goods-in delivery */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

$notice = '';

/* ---------- INSERT on POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;
    $ref = trim($_POST['ref']);

    if ($productId > 0 && $qty > 0) {
        try {
            Database::transaction(function () use ($productId, $qty, $ref) {
                // Lock the product row before adjusting stock.
                $product = Database::query(
                    "SELECT id FROM products WHERE id = :id FOR UPDATE",
                    array(':id' => $productId)
                )->fetch();

                if (!$product) {
                    throw new RuntimeException('Product not found.');
                }

                Database::query(
                    "INSERT INTO deliveries (product_id, qty, received_at, supplier_ref)
                     VALUES (:product_id, :qty, NOW(), :ref)",
                    array(
                        ':product_id' => $productId,
                        ':qty' => $qty,
                        ':ref' => $ref
                    )
                );

                Database::query(
                    "UPDATE products SET stock = stock + :qty WHERE id = :id",
                    array(':qty' => $qty, ':id' => $productId)
                );
            });

            header('Location: deliveries.php?msg=added');
            exit();
        } catch (RuntimeException $runtimeException) {
            $notice = $runtimeException->getMessage();
        } catch (Exception $exception) {
            $notice = 'Delivery could not be saved. Please try again.';
        }
    } else {
        $notice = 'Select a product and enter a quantity.';
    }
}

/* ---------- Build product dropdown ---------- */
$prods = Database::query("SELECT id, sku, name FROM products ORDER BY name")->fetchAll();
?>
<h2>Record Delivery</h2>

<?php if ($notice): ?>
    <p class="notice"><?php echo htmlspecialchars($notice); ?></p>
<?php endif; ?>

<form action="delivery_add.php" method="post">
    <label>Product
        <select name="product_id" required>
            <option value="">-- select --</option>
            <?php foreach ($prods as $p): ?>
                <option value="<?php echo $p['id']; ?>">
                    <?php echo $p['sku'].' - '.htmlspecialchars($p['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Quantity
        <input type="number" name="qty" min="1" required>
    </label>

    <label>Supplier Ref
        <input type="text" name="ref">
    </label>

    <p>
        <input type="submit" value="Save Delivery">
        <a href="deliveries.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
