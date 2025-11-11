<?php
/* customer_add.php – Create a new customer */
include 'includes/db.php';
include 'includes/header.php';

/* ------- Handle INSERT ------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = mysql_real_escape_string($_POST['name']);
    $phone   = mysql_real_escape_string($_POST['phone']);
    $email   = mysql_real_escape_string($_POST['email']);
    $address = mysql_real_escape_string($_POST['address']);

    mysql_query("
        INSERT INTO customers (name, phone, email, address)
        VALUES ('$name', '$phone', '$email', '$address')
    ");

    header('Location: customers.php?msg=added');
    exit();
}
?>
<h2>Add Customer</h2>

<form action="customer_add.php" method="post">
    <label>Name:
        <input type="text" name="name" required>
    </label>

    <label>Phone:
        <input type="text" name="phone">
    </label>

    <label>Email:
        <input type="text" name="email">
    </label>

    <label>Address:
        <textarea name="address" rows="3"></textarea>
    </label>

    <p>
        <input type="submit" value="Add Customer">
        <a href="customers.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
