<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* join categories so we can show the name */
$stmt = Database::query("
    SELECT p.id, p.sku, p.name, p.price, p.stock,
           IFNULL(c.name,'-') AS category
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.name
");
$products = $stmt->fetchAll();

$flash = '';
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':
            $flash = '<p class="notice">Product added.</p>';
            break;
        case 'updated':
            $flash = '<p class="notice">Product updated.</p>';
            break;
        case 'deleted':
            $flash = '<p class="notice">Product deleted.</p>';
            break;
        case 'in_use':
            $flash = '<p class="notice">Product is referenced by other records and cannot be deleted yet.</p>';
            break;
        case 'error':
            $flash = '<p class="notice">Action failed. Please try again.</p>';
            break;
    }
}
?>
<h2>Products</h2>

<?php echo $flash; ?>

<p>
    <a href="add_product.php" class="btn">+ Add Product</a>
</p>

<table>
    <thead>
        <tr>
            <th>SKU</th><th>Name</th><th>Category</th>
            <th>Price (£)</th><th>Stock</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
<?php if (!$products): ?>
        <tr><td colspan="6">No products yet.</td></tr>
<?php else: foreach ($products as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['sku']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['category']); ?></td>
            <td><?php echo number_format($row['price'],2); ?></td>
            <td><?php echo $row['stock']; ?></td>
            <td>
                <!-- NEW links -->
                <a href="product_edit.php?id=<?php echo $row['id']; ?>">Edit</a> |
                <a href="product_delete.php?id=<?php echo $row['id']; ?>"
                   onclick="return confirm('Delete this product?');">Delete</a>
            </td>
        </tr>
<?php endforeach; endif; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
