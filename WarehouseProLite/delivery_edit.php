<?php
/* delivery_edit.php – update an existing delivery */
include 'includes/db.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ---------- Update on POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pidNew = (int)$_POST['product_id'];
    $qtyNew = (int)$_POST['qty'];
    $refNew = mysql_real_escape_string($_POST['ref']);

    /* fetch old row (needed if you adjust stock) */
    $old = mysql_fetch_assoc(mysql_query("SELECT product_id, qty FROM deliveries WHERE id=$id"));

    mysql_query("
        UPDATE deliveries
        SET product_id = $pidNew,
            qty        = $qtyNew,
            supplier_ref = '$refNew'
        WHERE id = $id
    ");

    $delta = $qtyNew - $old['qty'];

/* same SKU updated */
if ($pidNew == $old['product_id']) {
    mysql_query("
        UPDATE products
        SET stock = stock + $delta
        WHERE id = $pidNew
    ");
}
/* SKU was changed */
else {
    /* subtract from old product */
    mysql_query("
        UPDATE products
        SET stock = stock - {$old['qty']}
        WHERE id = {$old['product_id']}
    ");
    /* add to new product */
    mysql_query("
        UPDATE products
        SET stock = stock + $qtyNew
        WHERE id = $pidNew
    ");
}


    header('Location: deliveries.php?msg=updated');
    exit();
}

/* ---------- Load existing row ---------- */
$row = mysql_fetch_assoc(mysql_query("
    SELECT d.*, p.sku, p.name
    FROM deliveries d
    JOIN products p ON p.id = d.product_id
    WHERE d.id = $id
"));
if (!$row) {
    echo "<p class='notice'>Delivery not found.</p>";
    include 'includes/footer.php';
    exit();
}

/* Products for dropdown */
$prods = mysql_query("SELECT id, sku, name FROM products ORDER BY name");
?>
<h2>Edit Delivery #<?php echo $id; ?></h2>

<form action="delivery_edit.php?id=<?php echo $id; ?>" method="post">
    <label>Product
        <select name="product_id" required>
            <?php while ($p = mysql_fetch_assoc($prods)): ?>
                <option value="<?php echo $p['id']; ?>"
                    <?php if ($p['id'] == $row['product_id']) echo 'selected'; ?>>
                    <?php echo $p['sku'].' - '.htmlspecialchars($p['name']); ?>
                </option>
            <?php endwhile; ?>
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
