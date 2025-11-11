<?php
include 'includes/db.php';
include 'includes/header.php';

/* join categories so we can show the name */
$res = mysql_query("
    SELECT p.id, p.sku, p.name, p.price, p.stock,
           IFNULL(c.name,'-') AS category
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.name
");
?>
<h2>Products</h2>

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
<?php if (mysql_num_rows($res) === 0): ?>
        <tr><td colspan="6">No products yet.</td></tr>
<?php else: while ($row = mysql_fetch_assoc($res)): ?>
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
<?php endwhile; endif; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
