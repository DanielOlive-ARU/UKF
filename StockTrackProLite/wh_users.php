<?php
/**
 * wh_users.php - List warehouse users (admin only, managed from office)
 */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* Admin-only access */
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php?msg=denied');
    exit();
}

/* Fetch all warehouse users (alphabetical) */
$users = Database::query(
    "SELECT id, username, role
     FROM wh_users
     ORDER BY username ASC"
)->fetchAll();

/* Flash message */
$flash = '';
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':
            $flash = '<p class="notice">Warehouse user added.</p>';
            break;
        case 'updated':
            $flash = '<p class="notice">Warehouse user updated.</p>';
            break;
        case 'deleted':
            $flash = '<p class="notice">Warehouse user deleted.</p>';
            break;
        case 'in_use':
            $flash = '<p class="notice">Cannot delete – user has associated records.</p>';
            break;
        case 'error':
            $flash = '<p class="notice">Action failed. Please try again.</p>';
            break;
        case 'csrf':
            $flash = '<p class="notice">Session expired. Please resubmit the action.</p>';
            break;
    }
}
?>
<h2>Warehouse Users</h2>

<?php echo $flash; ?>

<p>
    <a href="wh_user_add.php" class="btn">+ Add Warehouse User</a>
</p>

<table>
    <thead>
        <tr>
            <th>Username</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
<?php if ($users): ?>
    <?php foreach ($users as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo htmlspecialchars($row['role']); ?></td>
            <td>
                <a class="action-link" href="wh_user_edit.php?id=<?php echo $row['id']; ?>">Edit</a> |
                <form action="wh_user_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this warehouse user?');">
                    <?php echo Csrf::field('stock_wh_user_delete'); ?>
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <button type="submit" style="background:none;border:none;color:#06c;padding:0;cursor:pointer;text-decoration:underline;">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
        <tr><td colspan="3">No warehouse users found.</td></tr>
<?php endif; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
