<?php
/* customer_add.php – Create a new customer */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* ------- Handle INSERT ------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'stock_customer_add')) {
        header('Location: customers.php?msg=csrf');
        exit();
    }

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
    <?php echo Csrf::field('stock_customer_add'); ?>
    <label>Name:
        <input type="text" name="name" required
               pattern="[A-Za-z0-9._%+\-!?]{1,}"
               title="Customer name should contain letters, numbers, and common punctuation.">
    </label>

    <label>Phone:
        <input type="text" name="phone" required
               pattern="[+0-9][0-9]{10,13}"
               title="Phone number must be 11-14 digits, optionally starting with +.">
    </label>

    <label>Email:
        <input type="text" name="email" required
               pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-z]{2,}$"
               title="Please enter a valid email address.">
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
