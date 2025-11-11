<?php
/* includes/header.php */
session_start();
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
        <a href="logout.php" class="right">Logout</a>
    </nav>
</header>

<main class="container">
