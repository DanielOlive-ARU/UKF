<?php
/* delivery_add.php – record a goods-in delivery */
include 'includes/db.php';
include 'includes/header.php';

/* ---------- INSERT on POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)$_POST['product_id'];
    $qty = (int)$_POST['qty'];
    $ref = mysql_real_escape_string($_POST['ref']);

    mysql_query("
        INSERT INTO deliveries (product_id, qty, received_at, supplier_ref)
        VALUES ($pid, $qty, NOW(), '$ref')
    ");

        /* bump current stock */
    mysql_query("
        UPDATE products
        SET stock = stock + $qty
        WHERE id = $pid
    ");

    header('Location: deliveries.php?msg=added');
    exit();
}

/* ---------- Build product dropdown ---------- */
$prods = mysql_query("SELECT id, sku, name FROM products ORDER BY name");
?>
<h2>Record Delivery</h2>

<form action="delivery_add.php" method="post">
    <label>Product
        <select name="product_id" required>
            <option value="">-- select --</option>
            <?php while ($p = mysql_fetch_assoc($prods)): ?>
                <option value="<?php echo $p['id']; ?>">
                    <?php echo $p['sku'].' - '.htmlspecialchars($p['name']); ?>
                </option>
            <?php endwhile; ?>
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
