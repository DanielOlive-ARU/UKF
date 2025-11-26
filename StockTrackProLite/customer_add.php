<?php
/* customer_add.php – Create a new customer */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* ------- Handle INSERT ------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']);
    $phone   = trim($_POST['phone']);
    $email   = trim($_POST['email']);
    $address = trim($_POST['address']);

    Database::query(
        "INSERT INTO customers (name, phone, email, address)
         VALUES (:name, :phone, :email, :address)",
        array(
            ':name' => $name,
            ':phone' => $phone,
            ':email' => $email,
            ':address' => $address
        )
    );

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
