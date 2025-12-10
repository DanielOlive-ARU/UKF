<?php
/* customer_view.php – Show a customer's details and order history */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
/* Handle notes POST actions (add / delete) */
$post_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // add note
        if (isset($_POST['action']) && $_POST['action'] === 'add_note') {
            if (!Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'customer_note_add')) {
                header('Location: customers.php?msg=csrf');
                exit();
            }
            $noteText = isset($_POST['note']) ? trim($_POST['note']) : '';
            if ($noteText !== '') {
                Database::query(
                    "INSERT INTO customer_notes (customer_id, note, created_at) VALUES (:cid, :note, :created_at)",
                    array(':cid' => $id, ':note' => $noteText, ':created_at' => date('Y-m-d H:i:s'))
                );
            }
            header('Location: customer_view.php?id=' . $id);
            exit();
        }

        // delete note
        if (isset($_POST['action']) && $_POST['action'] === 'delete_note') {
            if (!Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'customer_note_delete')) {
                header('Location: customers.php?msg=csrf');
                exit();
            }
            $noteId = isset($_POST['note_id']) ? (int)$_POST['note_id'] : 0;
            if ($noteId) {
                Database::query(
                    "DELETE FROM customer_notes WHERE id = :nid AND customer_id = :cid",
                    array(':nid' => $noteId, ':cid' => $id)
                );
            }
            header('Location: customer_view.php?id=' . $id);
            exit();
        }
    } catch (Exception $e) {
        $post_error = $e->getMessage();
    }
}

try {
    /* Load customer using PDO */
    $customer = Database::fetchOne(
        "SELECT id, name, phone, email, address FROM customers WHERE id = ?",
        [$id]
    );
    
    if (!$customer) {
        echo '<p class="notice">Customer not found.</p>';
        include 'includes/footer.php';
        exit();
    }

    /* Load orders for this customer using PDO */
    $orders = Database::query(
        "SELECT id, order_date, total FROM orders WHERE customer_id = ? ORDER BY order_date DESC",
        [$id]
    )->fetchAll();

    /* Order count & totals using PDO */
    $summary = Database::fetchOne(
        "SELECT COUNT(*) as cnt, COALESCE(SUM(total), 0) as total_sum FROM orders WHERE customer_id = ?",
        [$id]
    );

    /* Spending by year (total amount and order count per year) */
    $spend_by_year = Database::query(
        "SELECT YEAR(order_date) AS yr, COUNT(*) AS orders_count, COALESCE(SUM(total),0) AS total_sum
         FROM orders
         WHERE customer_id = ?
         GROUP BY YEAR(order_date)
         ORDER BY yr DESC",
        [$id]
    )->fetchAll();

    /* Load notes for this customer */
    $notes = Database::query(
        "SELECT id, note, created_at FROM customer_notes WHERE customer_id = ? ORDER BY created_at DESC",
        [$id]
    )->fetchAll();
} catch (Exception $e) {
    echo '<p class="notice">Error loading customer data: ' . htmlspecialchars($e->getMessage()) . '</p>';
    include 'includes/footer.php';
    exit();
}

?>
<h2>Customer: <?php echo htmlspecialchars($customer['name']); ?></h2>
<p>
    <strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?><br>
    <strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?><br>
    <strong>Address:</strong> <?php echo nl2br(htmlspecialchars($customer['address'])); ?>
</p>

<h3>Order History</h3>
<p><?php echo (int)$summary['cnt']; ?> orders — Total spent: £<?php echo number_format($summary['total_sum'], 2); ?></p>

<!-- Customer Notes -->

<!-- Spending by Year -->
<?php if (!empty($spend_by_year)): ?>
    <h4>Spending by Year</h4>
    <table style="margin-bottom:12px;">
        <thead><tr><th>Year</th><th>Orders</th><th>Total (£)</th></tr></thead>
        <tbody>
        <?php foreach ($spend_by_year as $y): ?>
            <tr>
                <td><?php echo htmlspecialchars($y['yr']); ?></td>
                <td><?php echo (int)$y['orders_count']; ?></td>
                <td><?php echo number_format($y['total_sum'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h3>Notes</h3>
<?php if ($post_error): ?>
    <p class="notice">Error saving note: <?php echo htmlspecialchars($post_error); ?></p>
<?php endif; ?>
<form method="post" action="customer_view.php?id=<?php echo $id; ?>">
    <?php echo Csrf::field('customer_note_add'); ?>
    <input type="hidden" name="action" value="add_note">
    <label>
        <textarea name="note" rows="4" style="width:100%;box-sizing:border-box;" placeholder="Write a note about this customer..."></textarea>
    </label>
    <p>
        <input type="submit" value="Save Note">
    </p>
</form>

<?php if ($notes): ?>
    <table>
        <thead><tr><th>Date</th><th>Note</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($notes as $n): ?>
            <tr>
                <td><?php echo date('Y-m-d H:i', strtotime($n['created_at'])); ?></td>
                <td><?php echo nl2br(htmlspecialchars($n['note'])); ?></td>
                <td>
                    <form method="post" action="customer_view.php?id=<?php echo $id; ?>" style="display:inline" onsubmit="return confirm('Delete this note?');">
                        <?php echo Csrf::field('customer_note_delete'); ?>
                        <input type="hidden" name="action" value="delete_note">
                        <input type="hidden" name="note_id" value="<?php echo (int)$n['id']; ?>">
                        <button type="submit" style="background:none;border:none;color:#06c;padding:0;cursor:pointer;text-decoration:underline;">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No notes for this customer.</p>
<?php endif; ?>

<table>
    <thead><tr><th>#</th><th>Date</th><th>Total (£)</th><th>Actions</th></tr></thead>
    <tbody>
<?php if (!$orders): ?>
        <tr><td colspan="4">No orders for this customer.</td></tr>
<?php else: foreach ($orders as $o): ?>
        <tr>
            <td><?php echo $o['id']; ?></td>
            <td><?php echo date('Y-m-d H:i', strtotime($o['order_date'])); ?></td>
            <td><?php echo number_format($o['total'], 2); ?></td>
            <td><a class="action-link" href="order_view.php?id=<?php echo $o['id']; ?>">View</a></td>
        </tr>
<?php endforeach; endif; ?>
    </tbody>
</table>

<p><a href="customers.php">← Back to Customers</a></p>

<?php include 'includes/footer.php'; ?>
