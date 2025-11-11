<?php
/* reports.php – reporting hub (very legacy visual) */
include 'includes/db.php';
include 'includes/header.php';

/* 1. Monthly sales (last 12 months) */
$monthly = mysql_query("
    SELECT DATE_FORMAT(order_date,'%Y-%m') AS ym,
           COUNT(*)  AS orders,
           SUM(total) AS revenue
    FROM orders
    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY ym
    ORDER BY ym
");

/* 2. Top 5 customers by spend (last 12 months) */
$topCust = mysql_query("
    SELECT c.name,
           COUNT(o.id)  AS num_orders,
           SUM(o.total) AS spend
    FROM orders o
    JOIN customers c ON c.id = o.customer_id
    WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY c.id
    ORDER BY spend DESC
    LIMIT 5
");

/* 3. Low-stock products (< 20) */
$lowStock = mysql_query("
    SELECT sku, name, stock
    FROM products
    WHERE stock < 20
    ORDER BY stock ASC
");

/* Build arrays for the (very old) Chart.js v1 API */
$labels   = [];
$revenues = [];
while ($row = mysql_fetch_assoc($monthly)) {
    $labels[]   = $row['ym'];
    $revenues[] = round($row['revenue'], 2);
}
?>

<h2>Reports</h2>

<h3>Monthly Sales (last 12 months)</h3>
<canvas id="salesChart" height="120"></canvas>

<!-- ⚠ LEGACY/INSECURE CDN – Chart.js v1.0.2 (HTTP, no SRI) -->
<script src="http://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.min.js"></script>
<script>
var ctx  = document.getElementById('salesChart').getContext('2d');
var data = {
    labels: <?php echo json_encode($labels); ?>,
    datasets: [{
        label: "Revenue £",
        fillColor   : "#2e8b57",
        strokeColor : "#256b44",
        data        : <?php echo json_encode($revenues); ?>
    }]
};
/* Old v1 API: new Chart(ctx).Bar(...) */
new Chart(ctx).Bar(data, {
    responsive: true,
    scaleLabel: "£<%=value%>"
});
</script>

<h3>Top 5 Customers (last 12 months)</h3>
<table>
    <thead><tr><th>Customer</th><th>Orders</th><th>Spend (£)</th></tr></thead>
    <tbody>
    <?php while ($row = mysql_fetch_assoc($topCust)): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo $row['num_orders']; ?></td>
            <td><?php echo number_format($row['spend'], 2); ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<h3>Low-stock Products (stock &lt; 20)</h3>
<table>
    <thead><tr><th>SKU</th><th>Name</th><th>Stock</th></tr></thead>
    <tbody>
    <?php if (mysql_num_rows($lowStock) === 0): ?>
        <tr><td colspan="3">No items below threshold.</td></tr>
    <?php else: while ($row = mysql_fetch_assoc($lowStock)): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['sku']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo $row['stock']; ?></td>
        </tr>
    <?php endwhile; endif; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
