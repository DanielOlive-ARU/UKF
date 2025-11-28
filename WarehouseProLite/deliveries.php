<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* ---------- flash notice ---------- */
$flash = '';
if (isset($_GET['msg'])) {
  if ($_GET['msg'] === 'added')   $flash = '<p class="notice">Delivery recorded.</p>';
  if ($_GET['msg'] === 'updated') $flash = '<p class="notice">Delivery updated.</p>';
  if ($_GET['msg'] === 'deleted') $flash = '<p class="notice">Delivery deleted.</p>';
  if ($_GET['msg'] === 'error')   $flash = '<p class="notice">Delivery action failed. Please try again.</p>';
}

/* ---------- fetch deliveries ---------- */
$deliveries = Database::query(
  "SELECT d.id,
      d.received_at,
      d.qty,
      d.supplier_ref,
      p.sku,
      p.name
   FROM deliveries d
   JOIN products   p ON p.id = d.product_id
   ORDER BY d.received_at DESC"
)->fetchAll();
?>
<h2>Deliveries</h2>
<?php echo $flash; ?>

<p><a href="delivery_add.php" class="btn">+ Record Delivery</a></p>

<table>
  <thead>
    <tr>
      <th>ID</th><th>Date/Time</th><th>SKU</th><th>Name</th>
      <th>Qty</th><th>Supplier Ref</th><th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php if (!$deliveries): ?>
      <tr><td colspan="7">No deliveries recorded.</td></tr>
  <?php else: foreach ($deliveries as $r): ?>
      <tr>
        <td><?php echo $r['id']; ?></td>
        <td><?php echo $r['received_at']; ?></td>
        <td><?php echo $r['sku']; ?></td>
        <td><?php echo htmlspecialchars($r['name']); ?></td>
        <td><?php echo $r['qty']; ?></td>
        <td><?php echo htmlspecialchars($r['supplier_ref']); ?></td>
        <td>
          <a href="delivery_edit.php?id=<?php echo $r['id']; ?>">Edit</a> |
          <a href="delivery_delete.php?id=<?php echo $r['id']; ?>"
             onclick="return confirm('Delete this delivery?');">Delete</a>
        </td>
      </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>

<?php include 'includes/footer.php'; ?>
