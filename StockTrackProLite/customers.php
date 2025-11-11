<?php
/* customers.php  –  List + simple actions */
include 'includes/db.php';
include 'includes/header.php';

/* Fetch all customers (alphabetical) */
$result = mysql_query("
    SELECT id, name, phone, email, address
    FROM customers
    ORDER BY name ASC
");

/* Flash message */
$flash = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $flash = '<p class="notice">Customer deleted.</p>';
}
?>
<h2>Customers</h2>

<?php echo $flash; ?>

<p>
    <a href="customer_add.php" class="btn">+ Add Customer</a>
</p>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Phone</th>
            <th>Email</th>
            <th class="wide">Address</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
<?php if (mysql_num_rows($result) > 0): ?>
    <?php while ($row = mysql_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['phone']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo nl2br(htmlspecialchars($row['address'])); ?></td>
            <td>
                <a href="customer_edit.php?id=<?php echo $row['id']; ?>">Edit</a> |
                <a href="customer_delete.php?id=<?php echo $row['id']; ?>"
                   onclick="return confirm('Delete this customer?');">Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
        <tr><td colspan="5">No customers found.</td></tr>
<?php endif; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
