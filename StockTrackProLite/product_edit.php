<?php
include 'includes/db.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ---------- save ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku  = mysql_real_escape_string($_POST['sku']);
    $name = mysql_real_escape_string($_POST['name']);
    $cat  = (int)$_POST['category_id'];
    $price= (float)$_POST['price'];
    $stock= (int)$_POST['stock'];

    mysql_query("
        UPDATE products SET
            sku='$sku', name='$name',
            category_id=".($cat?:'NULL').",
            price=$price, stock=$stock
        WHERE id=$id
    ");
    header('Location: products.php?msg=updated');
    exit();
}

/* load row */
$row = mysql_fetch_assoc(mysql_query("SELECT * FROM products WHERE id=$id"));
if (!$row) { echo "<p class='notice'>Product not found.</p>"; include 'includes/footer.php'; exit; }

/* categories for dropdown */
$cats = mysql_query("SELECT id, name FROM categories ORDER BY name");
?>
<h2>Edit Product</h2>

<form action="product_edit.php?id=<?php echo $id; ?>" method="post">
    <label>SKU
        <input type="text" name="sku" value="<?php echo htmlspecialchars($row['sku']); ?>" required>
    </label>

    <label>Name
        <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
    </label>

    <label>Category
        <select name="category_id">
            <option value="">- none -</option>
            <?php while ($c = mysql_fetch_assoc($cats)): ?>
                <option value="<?php echo $c['id']; ?>"
                    <?php if ($c['id']==$row['category_id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </label>

    <label>Price (£)
        <input type="number" step="0.01" name="price"
               value="<?php echo number_format($row['price'],2,'.',''); ?>" required>
    </label>

    <label>Stock
        <input type="number" name="stock" value="<?php echo $row['stock']; ?>" required>
    </label>

    <p>
        <input type="submit" value="Save">
        <a href="products.php">Cancel</a>
    </p>
</form>
<?php include 'includes/footer.php'; ?>
