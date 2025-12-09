<?php
/* customer_view.php – Show a customer's details and order history */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    /* Load customer using PDO */
    $customer = Database::fetchOne(
        "SELECT id, name, phone, email, address FROM customers WHERE id = ?",
        [$id]
    );
    
    if (!$customer) {
        echo '<p class="notice">Customer not found.</p>';
        include 'includes/footer.php';
        exit();
    }

    /* Load orders for this customer using PDO */
    $orders = Database::query(
        "SELECT id, order_date, total FROM orders WHERE customer_id = ? ORDER BY order_date DESC",
        [$id]
    )->fetchAll();

    /* Order count & totals using PDO */
    $summary = Database::fetchOne(
        "SELECT COUNT(*) as cnt, COALESCE(SUM(total), 0) as total_sum FROM orders WHERE customer_id = ?",
        [$id]
    );
} catch (Exception $e) {
    echo '<p class="notice">Error loading customer data: ' . htmlspecialchars($e->getMessage()) . '</p>';
    include 'includes/footer.php';
    exit();
}

?>
<h2>Customer: <?php echo htmlspecialchars($customer['name']); ?></h2>
<p>
    <strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?><br>
    <strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?><br>
    <strong>Address:</strong> <?php echo nl2br(htmlspecialchars($customer['address'])); ?>
</p>

<h3>Order History</h3>
<p><?php echo (int)$summary['cnt']; ?> orders — Total spent: £<?php echo number_format($summary['total_sum'], 2); ?></p>

<table>
    <thead><tr><th>#</th><th>Date</th><th>Total (£)</th><th>Actions</th></tr></thead>
    <tbody>
<?php if (!$orders): ?>
        <tr><td colspan="4">No orders for this customer.</td></tr>
<?php else: foreach ($orders as $o): ?>
        <tr>
            <td><?php echo $o['id']; ?></td>
            <td><?php echo date('Y-m-d H:i', strtotime($o['order_date'])); ?></td>
            <td><?php echo number_format($o['total'], 2); ?></td>
            <td><a class="action-link" href="order_view.php?id=<?php echo $o['id']; ?>">View</a></td>
        </tr>
<?php endforeach; endif; ?>
    </tbody>
</table>

<p><a href="customers.php">← Back to Customers</a></p>

<?php include 'includes/footer.php'; ?>
