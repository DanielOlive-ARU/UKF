<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Login – StockTrack Pro Lite</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">

<div class="login-card">
    <div align="center">
        <h1>📦 StockTrack Pro Lite</h1>
        <p>Manage your inventory with ease.</p>
    </div>
    <p><img src="assets/UKFruit2010.png" width="400" alt="Customer Logo" class="logo"></p>

    <form action="login.php" method="post">
        <label>Username
            <input type="text" name="username" required>
        </label>

        <label>Password
            <input type="password" name="password" required>
        </label>

        <input type="submit" value="Login">
    </form>
</div>

</body>
</html>
