<?php
/* includes/header.php */
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/../includes/security.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>StockTrack Pro Lite</title>

    <!-- stylesheet -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <h1 class="logo">📦 StockTrack Pro Lite</h1>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="orders.php">Orders</a>
        <a href="customers.php">Customers</a>
        <a href="reports.php">Reports</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="users.php">Users</a>
        <a href="wh_users.php">WH Users</a>
        <?php endif; ?>
        <a href="logout.php" class="right">Logout</a>
    </nav>
</header>

<main class="container">
