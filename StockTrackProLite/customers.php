<?php
/* customers.php  –  List + simple actions */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* Fetch all customers (alphabetical) */
$customers = Database::query(
    "SELECT id, name, phone, email, address
     FROM customers
     ORDER BY name ASC"
)->fetchAll();

/* Flash message */
$flash = '';
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':
            $flash = '<p class="notice">Customer added.</p>';
            break;
        case 'updated':
            $flash = '<p class="notice">Customer updated.</p>';
            break;
        case 'deleted':
            $flash = '<p class="notice">Customer deleted.</p>';
            break;
        case 'in_use':
            $flash = '<p class="notice">Customer linked to existing records and cannot be deleted yet.</p>';
            break;
        case 'error':
            $flash = '<p class="notice">Action failed. Please try again.</p>';
            break;
    }
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
<?php if ($customers): ?>
    <?php foreach ($customers as $row): ?>
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
    <?php endforeach; ?>
<?php else: ?>
        <tr><td colspan="5">No customers found.</td></tr>
<?php endif; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
