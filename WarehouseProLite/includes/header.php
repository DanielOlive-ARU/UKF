<?php
/**
 * Shared header for WarehouseProLite pages – enforces authentication.
 */
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/../includes/security.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>WarehouseProLite</title>

    <!-- ✅ add / verify this line -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class='topbar'>
  <h1 class='logo'>🏭 WarehouseProLite</h1>
  <nav>
    <a href='dashboard.php'   class='<?php echo basename($_SERVER['PHP_SELF'])=='dashboard.php'   ? 'active' :'';?>'>Dashboard</a>
    <a href='deliveries.php'  class='<?php echo basename($_SERVER['PHP_SELF'])=='deliveries.php'  ? 'active' :'';?>'>Deliveries</a>
    <a href='stocktake_new.php' class='<?php echo basename($_SERVER['PHP_SELF'])=='stocktake_new.php' ? 'active' :'';?>'>Stock-Take</a>
    <a href='stocktakes.php' class='<?php echo basename($_SERVER["PHP_SELF"])=="stocktakes.php"?"active":"";?>'>Take History</a>
    <a href='adjustments.php' class='<?php echo basename($_SERVER['PHP_SELF'])=='adjustments.php' ? 'active' :'';?>'>Adjustments</a>
    <a href='qa_samples.php'  class='<?php echo basename($_SERVER['PHP_SELF'])=='qa_samples.php'  ? 'active' :'';?>'>QA Samples</a>
    <a href='label_print.php' class='<?php echo basename($_SERVER['PHP_SELF'])=='label_print.php' ? 'active' :'';?>'>Labels</a>
    <a href='reports.php'     class='<?php echo basename($_SERVER['PHP_SELF'])=='reports.php'     ? 'active' :'';?>'>Reports</a>
    <a href='logout.php' class='right'>Logout</a>
  </nav>
</header>
<main class='container'>
