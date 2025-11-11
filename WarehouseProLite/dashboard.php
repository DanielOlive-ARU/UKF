<?php
/* dashboard.php – WarehouseProLite overview */
include 'includes/db.php';
include 'includes/header.php';

/* ------- KPI queries (all legacy mysql_* calls) ------- */
$totalDeliveries = mysql_result(
    mysql_query("SELECT COUNT(*) FROM deliveries"), 0);

$todayDeliveries = mysql_result(
    mysql_query("SELECT COUNT(*) FROM deliveries
                 WHERE DATE(received_at) = CURDATE()"), 0);

$recentAdjust = mysql_result(
    mysql_query("SELECT COUNT(*) FROM adjustments
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"), 0);

$recentQAFails = mysql_result(
    mysql_query("SELECT COUNT(*) FROM qa_samples
                 WHERE passed = 'no'
                 AND sample_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"), 0);
?>
<h2>Dashboard</h2>

<div class="cards">
  <div class="card"><span class="big"><?php echo $totalDeliveries; ?></span>Total&nbsp;Deliveries</div>
  <div class="card"><span class="big"><?php echo $todayDeliveries; ?></span>Today</div>
  <div class="card"><span class="big"><?php echo $recentAdjust; ?></span>Adj&nbsp;(30d)</div>
  <div class="card"><span class="big"><?php echo $recentQAFails; ?></span>QA&nbsp;Fails&nbsp;(30d)</div>
</div>

<h3>Quick Links</h3>
<ul>
  <li><a href="deliveries.php">Record Deliveries</a></li>
  <li><a href="stocktake_new.php">Start Stock-Take</a></li>
  <li><a href="adjustments.php">Add Adjustment</a></li>
  <li><a href="qa_samples.php">Log QA Sample</a></li>
  <li><a href="reports.php">Warehouse Reports</a></li>
</ul>

<?php include 'includes/footer.php'; ?>
