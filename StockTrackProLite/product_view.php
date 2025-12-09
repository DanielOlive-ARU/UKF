<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    /* Load product */
    $product = Database::fetchOne(
        "SELECT p.id, p.sku, p.name, p.price, p.stock, IFNULL(c.name,'-') AS category
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.id = ?",
        [$id]
    );

    if (!$product) {
        echo '<p class="notice">Product not found.</p>';
        include 'includes/footer.php';
        exit();
    }

    /* Determine date 12 months ago */
    $startDate = date('Y-m-d H:i:s', strtotime('-12 months'));

    /* Load order lines for this product in the last 12 months */
    $lines = Database::query(
        "SELECT o.id AS order_id, o.order_date, oi.quantity, oi.price
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE oi.product_id = ? AND o.order_date >= ?
         ORDER BY o.order_date DESC",
        [$id, $startDate]
    )->fetchAll();

    /* Summary: orders count, total quantity, total revenue */
    $summary = Database::fetchOne(
        "SELECT COUNT(DISTINCT o.id) as cnt, COALESCE(SUM(oi.quantity),0) as qty_sum, COALESCE(SUM(oi.quantity * oi.price),0) as revenue_sum
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE oi.product_id = ? AND o.order_date >= ?",
        [$id, $startDate]
    );
} catch (Exception $e) {
    echo '<p class="notice">Error loading product data: ' . htmlspecialchars($e->getMessage()) . '</p>';
    include 'includes/footer.php';
    exit();
}

?>
<h2>Product: <?php echo htmlspecialchars($product['name']); ?></h2>
<p>
    <strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?><br>
    <strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?><br>
    <strong>Price:</strong> £<?php echo number_format($product['price'], 2); ?><br>
    <strong>Stock:</strong> <?php echo (int)$product['stock']; ?>
</p>

<h3>Order History — Last 12 months</h3>
<p><?php echo (int)$summary['cnt']; ?> orders — Total sold: <?php echo (int)$summary['qty_sum']; ?> units — Total revenue: £<?php echo number_format($summary['revenue_sum'], 2); ?></p>

<table>
    <thead><tr><th>#</th><th>Date</th><th>Qty</th><th>Unit £</th><th>Line £</th><th>Actions</th></tr></thead>
    <tbody>
<?php if (!$lines): ?>
        <tr><td colspan="6">No orders for this product in the last 12 months.</td></tr>
<?php else: foreach ($lines as $l): ?>
        <tr>
            <td><?php echo $l['order_id']; ?></td>
            <td><?php echo date('Y-m-d H:i', strtotime($l['order_date'])); ?></td>
            <td><?php echo (int)$l['quantity']; ?></td>
            <td><?php echo number_format($l['price'], 2); ?></td>
            <td><?php echo number_format($l['quantity'] * $l['price'], 2); ?></td>
            <td><a href="order_view.php?id=<?php echo $l['order_id']; ?>">View</a></td>
        </tr>
<?php endforeach; endif; ?>
    </tbody>
</table>

<p><a href="products.php">← Back to Products</a></p>

<?php include 'includes/footer.php'; ?>
