<?php
include 'includes/db.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* header */
$hdr = mysql_fetch_assoc(mysql_query("
    SELECT o.*, c.name AS customer
    FROM orders o
    LEFT JOIN customers c ON c.id = o.customer_id
    WHERE o.id=$id
"));
if (!$hdr) {
    echo '<p class="notice">Order not found.</p>';
    include 'includes/footer.php';
    exit();
}

/* lines */
$lines = mysql_query("
    SELECT oi.*, p.name
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id=$id
");
?>
<h2>Order #<?php echo $hdr['id']; ?></h2>
<p><strong>Date:</strong> <?php echo date('Y-m-d H:i', strtotime($hdr['order_date'])); ?><br>
<strong>Customer:</strong> <?php echo htmlspecialchars($hdr['customer']); ?><br>
<strong>Total:</strong> £<?php echo number_format($hdr['total'], 2); ?></p>

<table>
    <thead><tr><th>Product</th><th>Qty</th><th>Unit £</th><th>Line £</th></tr></thead>
    <tbody>
    <?php while ($l = mysql_fetch_assoc($lines)): ?>
        <tr>
            <td><?php echo htmlspecialchars($l['name']); ?></td>
            <td><?php echo $l['quantity']; ?></td>
            <td><?php echo number_format($l['price'], 2); ?></td>
            <td><?php echo number_format($l['quantity'] * $l['price'], 2); ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<p><a href="orders.php">← Back to Orders</a></p>

<?php include 'includes/footer.php'; ?>
