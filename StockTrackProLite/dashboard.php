<?php
/* dashboard.php – legacy “sexy” summary */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* --- Quick KPIs --- */
function fetchCount($sql)
{
    $row = Database::fetchOne($sql);
    return $row ? (int)array_shift($row) : 0;
}

$totCust   = fetchCount("SELECT COUNT(*) AS total FROM customers");
$totProd   = fetchCount("SELECT COUNT(*) AS total FROM products");
$totOrders = fetchCount("SELECT COUNT(*) AS total FROM orders");

/* low-stock threshold */
$lowCount = fetchCount("SELECT COUNT(*) AS total FROM products WHERE stock < 20");

/* orders this month */
$monthOrders = fetchCount(
        "SELECT COUNT(*) AS total
         FROM orders
         WHERE YEAR(order_date) = YEAR(CURDATE())
             AND MONTH(order_date) = MONTH(CURDATE())"
);

/* most recent 5 orders */
$recent = Database::query(
    "SELECT o.id, o.order_date, o.total, c.name AS customer
     FROM orders o
     LEFT JOIN customers c ON c.id = o.customer_id
     ORDER BY o.order_date DESC
     LIMIT 5"
)->fetchAll();
?>
<h2>Dashboard</h2>

<div class="cards">
    <div class="card kpi"><span class="big"><?php echo $totCust; ?></span>Customers</div>
    <div class="card kpi"><span class="big"><?php echo $totProd; ?></span>Products</div>
    <div class="card kpi"><span class="big"><?php echo $lowCount; ?></span>Low-stock&nbsp;items</div>
    <div class="card kpi"><span class="big"><?php echo $totOrders; ?></span>Total&nbsp;orders</div>
    <div class="card kpi"><span class="big"><?php echo $monthOrders; ?></span>Orders&nbsp;this&nbsp;month</div>
</div>

<h3>Recent Orders</h3>
<table>
    <thead><tr><th>#</th><th>Date</th><th>Customer</th><th>Total (£)</th></tr></thead>
    <tbody>
<?php foreach ($recent as $row): ?>
        <tr>
            <td><a href="order_view.php?id=<?php echo $row['id']; ?>">
                <?php echo $row['id']; ?></a></td>
            <td><?php echo date('Y-m-d', strtotime($row['order_date'])); ?></td>
            <td><?php echo htmlspecialchars($row['customer']); ?></td>
            <td><?php echo number_format($row['total'], 2); ?></td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
