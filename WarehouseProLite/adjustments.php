<?php
include 'includes/db.php';
include 'includes/header.php';

/* Flash message */
$flash = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $flash = '<p class="notice">Adjustment deleted.</p>';
}

/* Fetch journal */
$res = mysql_query("
    SELECT  a.id,
            a.qty_delta,
            a.reason,
            a.created_at,
            p.sku,
            p.name
    FROM adjustments a
    JOIN products p ON p.id = a.product_id
    ORDER BY a.created_at DESC
");
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
<?php if (mysql_num_rows($res) === 0): ?>
        <tr><td colspan="7">No adjustments yet.</td></tr>
<?php else: while ($r = mysql_fetch_assoc($res)): ?>
        <tr>
            <td><?php echo $r['id']; ?></td>
            <td><?php echo $r['created_at']; ?></td>
            <td><?php echo $r['sku']; ?></td>
            <td><?php echo htmlspecialchars($r['name']); ?></td>
            <td><?php echo $r['qty_delta']; ?></td>
            <td><?php echo $r['reason']; ?></td>
            <td>
                <a href="adjustment_delete.php?id=<?php echo $r['id']; ?>"
                   onclick="return confirm('Delete this adjustment?');">Delete</a>
            </td>
        </tr>
<?php endwhile; endif; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
