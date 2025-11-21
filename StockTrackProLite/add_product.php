<?php
include 'includes/db.php';
include 'includes/header.php';

/* ---------- Handle INSERT ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku   = mysql_real_escape_string($_POST['sku']);
    $name  = mysql_real_escape_string($_POST['name']);
    $cat   = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];

    mysql_query("
        INSERT INTO products (sku, name, category_id, price, stock)
        VALUES (
            '$sku',
            '$name',
            ".($cat ?: 'NULL').",
            $price,
            $stock
        )
    ");

    header('Location: products.php?msg=added');
    exit();
}

/* ------- Load categories for drop-down ------- */
$cats = mysql_query("SELECT id, name FROM categories ORDER BY name");
?>
<h2>Add Product</h2>
<!-- Formats SKU user input to force a specific pattern -->
<form action="add_product.php" method="post">
    <label>SKU
    <input type="text" name="sku" required
           pattern="[A-Z]{2}[._%+\-][A-Z]{3}[._%+\-][0-9]{3,}"
           title="SKU must consist of two capital letters, a dash, three capital letters, a dash, and at least three digits (e.g. AB-XYZ-123)"
           oninput="this.value=this.value.toUpperCase()">
    </label>

    <label>Name
        <input type="text" name="name" required>
    </label>

    <label>Category
        <select name="category_id">
            <option value="">- none -</option>
            <?php while ($c = mysql_fetch_assoc($cats)): ?>
                <option value="<?php echo $c['id']; ?>">
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </label>

    <label>Price (£)
        <input type="number" step="0.01" name="price" required>
    </label>

    <label>Stock
        <input type="number" name="stock" value="0" required>
    </label>

    <p>
        <input type="submit" value="Add Product">
        <a href="products.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
