<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* Flash message */
$flash = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') {
        $flash = '<p class="notice">Adjustment deleted.</p>';
    } elseif ($_GET['msg'] === 'added') {
        $flash = '<p class="notice">Adjustment saved.</p>';
    } elseif ($_GET['msg'] === 'error') {
        $flash = '<p class="notice">Adjustment action failed. Please try again.</p>';
    } elseif ($_GET['msg'] === 'csrf') {
        $flash = '<p class="notice">Session expired. Please retry the action.</p>';
    }
}

// Each delete button renders its own CSRF field.

/* Fetch journal */
$adjustments = Database::query(
    "SELECT  a.id,
             a.qty_delta,
             a.reason,
             a.created_at,
             p.sku,
             p.name
      FROM adjustments a
      JOIN products p ON p.id = a.product_id
      ORDER BY a.created_at DESC"
)->fetchAll();
?>
<h2>Adjustments</h2>
<?php echo $flash; ?>

<p><a href="adjustment_add.php" class="btn">+ Add Adjustment</a></p>

<table>
    <thead>
        <tr>
            <th>ID</th><th>Date</th><th>SKU</th><th>Name</th>
            <th>Δ Qty</th><th>Reason</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
<?php if (!$adjustments): ?>
        <tr><td colspan="7">No adjustments yet.</td></tr>
<?php else: foreach ($adjustments as $r): ?>
        <tr>
            <td><?php echo $r['id']; ?></td>
            <td><?php echo $r['created_at']; ?></td>
            <td><?php echo $r['sku']; ?></td>
            <td><?php echo htmlspecialchars($r['name']); ?></td>
            <td><?php echo $r['qty_delta']; ?></td>
            <td><?php echo $r['reason']; ?></td>
                                 <td>
                                       <form action="adjustment_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this adjustment?');">
                                             <?php echo Csrf::field('wh_adjustment_delete'); ?>
                                             <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                           <button type="submit" style="background:none;border:none;color:#06c;padding:0;cursor:pointer;text-decoration:underline;">Delete</button>
                                     </form>
                                 </td>
        </tr>
<?php endforeach; endif; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
