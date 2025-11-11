<?php
include 'includes/db.php';
include 'includes/header.php';

/* join customers so we can show their name */
$res = mysql_query("
    SELECT o.id, o.order_date, o.total,
           c.name AS customer
    FROM orders o
    LEFT JOIN customers c ON c.id = o.customer_id
    ORDER BY o.order_date DESC
");
?>
<h2>Orders</h2>

<p><a href="order_new.php" class="btn">+ New Order</a></p>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Total (£)</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
<?php if (mysql_num_rows($res) === 0): ?>
        <tr><td colspan="5">No orders yet.</td></tr>
<?php else:
      while ($row = mysql_fetch_assoc($res)): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo date('Y-m-d H:i', strtotime($row['order_date'])); ?></td>
            <td><?php echo htmlspecialchars($row['customer']); ?></td>
            <td><?php echo number_format($row['total'], 2); ?></td>
            <td><a href="order_view.php?id=<?php echo $row['id']; ?>">View</a></td>
        </tr>
<?php endwhile; endif; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
