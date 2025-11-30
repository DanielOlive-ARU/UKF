<?php
session_start();
require_once dirname(__DIR__) . '/includes/security.php';

if (isset($_SESSION['wh_user'])) {
    header('Location: dashboard.php');
    exit();
}

$errorMessage = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'csrf') {
        $errorMessage = 'Your session expired. Please try again.';
    } elseif ($_GET['error'] === 'locked') {
        $errorMessage = 'Too many failed attempts. Please wait one minute before trying again.';
    } else {
        $errorMessage = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Warehouse Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">

<div class="login-card">
        <h1>🏭 WarehouseProLite</h1>
        <p>Manage your inventory with ease.</p>
    <p><img src="assets/UKFruit2010.png" width="200" alt="Customer Logo" class="logo"></p>

    <?php if ($errorMessage !== ''): ?>
        <p style="color:#a00; margin-bottom:1rem;"><?php echo htmlspecialchars($errorMessage); ?></p>
    <?php endif; ?>

    <form action="login.php" method="post">
        <?php echo Csrf::field('wh_login'); ?>
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
