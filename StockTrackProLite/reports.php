<?php
/* reports.php – reporting hub (very legacy visual) */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* 1. Monthly sales (last 12 months) */
$monthly = Database::query(
    "SELECT DATE_FORMAT(order_date,'%Y-%m') AS ym,
            COUNT(*)  AS orders,
            SUM(total) AS revenue
     FROM orders
     WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY ym
     ORDER BY ym"
)->fetchAll();

/* --- load products for selector --- */
$prods = Database::query("SELECT id, sku, name FROM products ORDER BY name")->fetchAll();

/* read selected product (GET) */
$selectedProductId = isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : 0;
$selectedProductName = '';
if ($selectedProductId) {
    $prodRow = Database::query(
        "SELECT sku, name FROM products WHERE id = :id",
        array(':id' => $selectedProductId)
    )->fetch();
    if ($prodRow) {
        $selectedProductName = $prodRow['sku'].' - '.$prodRow['name'];
    } else {
        $selectedProductId = 0; // invalid id -> fallback
    }
}

/* if a product is selected, get its monthly quantities for the same 12-month window */
$monthlyProductSales = array();
if ($selectedProductId) {
    $rows = Database::query(
        "SELECT DATE_FORMAT(o.order_date,'%Y-%m') AS ym,
                SUM(oi.quantity) AS qty
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
           AND oi.product_id = :pid
         GROUP BY ym
         ORDER BY ym",
        array(':pid' => $selectedProductId)
    )->fetchAll();

    /* map month -> qty for quick lookup */
    $byYm = array();
    foreach ($rows as $r) {
        $byYm[$r['ym']] = (int)$r['qty'];
    }

    /* align with $monthly labels (ensures zero for months with no sales) */
    foreach ($monthly as $m) {
        $ym = $m['ym'];
        $monthlyProductSales[] = isset($byYm[$ym]) ? $byYm[$ym] : 0;
    }
}

/* Build arrays for Chart.js v1 */
$labels   = array();
$revenues = array();
foreach ($monthly as $row) {
    $labels[]   = $row['ym'];
    $revenues[] = round($row['revenue'], 2);
}

/* 2. Top 5 customers (last 12 months) */
$topCust = Database::query(
    "SELECT c.name,
            COUNT(DISTINCT o.id) AS num_orders,
            SUM(o.total) AS spend
     FROM orders o
     JOIN customers c ON c.id = o.customer_id
     WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY c.id
     ORDER BY spend DESC
     LIMIT 5"
)->fetchAll();

/* 3. Low-stock products (stock < 20) */
$lowStock = Database::query(
    "SELECT id, sku, name, stock
     FROM products
     WHERE stock < 20
     ORDER BY stock ASC"
)->fetchAll();

/* 4. Top 10 selling products by quantity (last 12 months) */
$topProducts = Database::query(
    "SELECT p.sku,
            p.name,
            SUM(oi.quantity) AS total_qty,
            SUM(oi.quantity * oi.price) AS total_revenue
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     JOIN orders o ON o.id = oi.order_id
     WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY p.id
     ORDER BY total_qty DESC
     LIMIT 10"
)->fetchAll();

/* 5. Monthly top-3 products (last 12 months) ordered by month DESC */
$monthlyProductRanks = Database::query(
    "SELECT DATE_FORMAT(o.order_date,'%Y-%m') AS ym,
            p.id AS product_id,
            p.sku,
            p.name,
            SUM(oi.quantity) AS qty,
            SUM(oi.quantity * oi.price) AS revenue
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     JOIN products p ON p.id = oi.product_id
     WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY ym, p.id
     ORDER BY ym DESC, qty DESC"
)->fetchAll();

/* Group by month and extract top 3 per month */
$trends = array();
foreach ($monthlyProductRanks as $r) {
    $ym = $r['ym'];
    if (!isset($trends[$ym])) {
        $trends[$ym] = array();
    }
    if (count($trends[$ym]) < 3) {
        $trends[$ym][] = $r;
    }
}
?>
<h2>Reports</h2>

<!-- product selector for the chart -->
<form method="get" action="reports.php" style="margin-bottom:12px;">
    <label style="font-weight:normal;">
        Show monthly for product:
        <select name="product_id">
            <option value="">-- overall revenue --</option>
            <?php foreach ($prods as $p): ?>
                <option value="<?php echo (int)$p['id']; ?>" <?php if ($selectedProductId == $p['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($p['sku'].' - '.$p['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <input type="submit" value="Apply">
    <?php if ($selectedProductId): ?>
        <a href="reports.php" style="margin-left:8px;">Reset</a>
    <?php endif; ?>
</form>

<h3>Monthly Sales (last 12 months)</h3>
<canvas id="salesChart" height="120"></canvas>

<!-- ⚠ LEGACY/INSECURE CDN – Chart.js v1.0.2 (HTTP, no SRI) -->
<script src="http://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.min.js"></script>
<script>
var ctx  = document.getElementById('salesChart').getContext('2d');
var labels = <?php echo json_encode($labels); ?>;

<?php if ($selectedProductId): ?>
// product view: show monthly quantities for the selected product
var data = {
    labels: labels,
    datasets: [{
        label: "<?php echo addslashes($selectedProductName); ?> — Qty Sold",
        fillColor   : "#2e86de",
        strokeColor : "#1f5fa6",
        data        : <?php echo json_encode($monthlyProductSales); ?>
    }]
};
new Chart(ctx).Bar(data, {
    responsive: true,
    scaleLabel: "<%=value%> units"
});
<?php else: ?>
// overall revenue view (existing behaviour)
var data = {
    labels: labels,
    datasets: [{
        label: "Revenue £",
        fillColor   : "#2e8b57",
        strokeColor : "#256b44",
        data        : <?php echo json_encode($revenues); ?>
    }]
};
new Chart(ctx).Bar(data, {
    responsive: true,
    scaleLabel: "£<%=value%>"
});
<?php endif; ?>
</script>

<h3>Top 5 Customers (last 12 months)</h3>
<table>
    <thead><tr><th>Customer</th><th>Orders</th><th>Spend (£)</th></tr></thead>
    <tbody>
    <?php if (!$topCust): ?>
        <tr><td colspan="3">No orders in the last 12 months.</td></tr>
    <?php else: foreach ($topCust as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo $row['num_orders']; ?></td>
            <td><?php echo number_format($row['spend'], 2); ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<h3>Low-stock Products (stock &lt; 20)</h3>
<table>
    <thead><tr><th>SKU</th><th>Name</th><th>Stock</th></tr></thead>
    <tbody>
    <?php if (!$lowStock): ?>
        <tr><td colspan="3">No items below threshold.</td></tr>
    <?php else: foreach ($lowStock as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['sku']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo $row['stock']; ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<h3>Top 10 Selling Products (last 12 months)</h3>
<table>
    <thead><tr><th>SKU</th><th>Name</th><th>Qty Sold</th><th>Revenue (£)</th></tr></thead>
    <tbody>
    <?php if (!$topProducts): ?>
        <tr><td colspan="4">No sales data available.</td></tr>
    <?php else: foreach ($topProducts as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['sku']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo (int)$row['total_qty']; ?></td>
            <td><?php echo number_format($row['total_revenue'], 2); ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<h3>Product Trends — Top 3 Per Month (last 12 months)</h3>
<table>
    <thead><tr><th>Month</th><th>#1 Product</th><th>Qty</th><th>#2 Product</th><th>Qty</th><th>#3 Product</th><th>Qty</th></tr></thead>
    <tbody>
    <?php if (empty($trends)): ?>
        <tr><td colspan="7">No trend data available.</td></tr>
    <?php else: foreach ($trends as $ym => $items): ?>
        <?php
            $r1 = $items[0] ?? null;
            $r2 = $items[1] ?? null;
            $r3 = $items[2] ?? null;
            $fmt = function($r) {
                if (!$r) return '—';
                return htmlspecialchars($r['sku'].' – '.$r['name']);
            };
        ?>
        <tr>
            <td><?php echo htmlspecialchars($ym); ?></td>
            <td><?php echo $fmt($r1); ?></td>
            <td><?php echo $r1 ? (int)$r1['qty'] : '—'; ?></td>
            <td><?php echo $fmt($r2); ?></td>
            <td><?php echo $r2 ? (int)$r2['qty'] : '—'; ?></td>
            <td><?php echo $fmt($r3); ?></td>
            <td><?php echo $r3 ? (int)$r3['qty'] : '—'; ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
