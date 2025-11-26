<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* ---------- Handle INSERT ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku   = trim($_POST['sku']);
    $name  = trim($_POST['name']);
    $cat   = ($_POST['category_id'] === '' ? null : (int)$_POST['category_id']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];

    Database::query(
        "INSERT INTO products (sku, name, category_id, price, stock)
         VALUES (:sku, :name, :category_id, :price, :stock)",
        array(
            ':sku' => $sku,
            ':name' => $name,
            ':category_id' => $cat,
            ':price' => $price,
            ':stock' => $stock
        )
    );

    header('Location: products.php?msg=added');
    exit();
}

/* ------- Load categories for drop-down ------- */
$cats = Database::query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
?>
<h2>Add Product</h2>

<form action="add_product.php" method="post">
    <label>SKU
        <input type="text" name="sku" required>
    </label>

    <label>Name
        <input type="text" name="name" required>
    </label>

    <label>Category
        <select name="category_id">
            <option value="">- none -</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?php echo $c['id']; ?>">
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
            <?php endforeach; ?>
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
