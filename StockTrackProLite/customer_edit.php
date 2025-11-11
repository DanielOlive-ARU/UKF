<?php
/* customer_edit.php – View / update single customer */
include 'includes/db.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ------- Handle SAVE (POST) ------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = mysql_real_escape_string($_POST['name']);
    $phone   = mysql_real_escape_string($_POST['phone']);
    $email   = mysql_real_escape_string($_POST['email']);
    $address = mysql_real_escape_string($_POST['address']);

    mysql_query("
        UPDATE customers
        SET name='$name',
            phone='$phone',
            email='$email',
            address='$address'
        WHERE id=$id
    ");

    header('Location: customers.php?msg=updated');
    exit();
}

/* ------- Load existing row ------- */
$res = mysql_query("SELECT * FROM customers WHERE id=$id");
if (!$row = mysql_fetch_assoc($res)) {
    echo '<p class="notice">Customer not found.</p>';
    include 'includes/footer.php';
    exit();
}
?>
<h2>Edit Customer</h2>

<form action="customer_edit.php?id=<?php echo $id; ?>" method="post">
    <label>Name:
        <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
    </label>

    <label>Phone:
        <input type="text" name="phone" value="<?php echo htmlspecialchars($row['phone']); ?>">
    </label>

    <label>Email:
        <input type="text" name="email" value="<?php echo htmlspecialchars($row['email']); ?>">
    </label>

    <label>Address:
        <textarea name="address" rows="3"><?php echo htmlspecialchars($row['address']); ?></textarea>
    </label>

    <p>
        <input type="submit" value="Save">
        <a href="customers.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
