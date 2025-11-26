<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

$takeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$notice = '';

/* ---------- Final-approve handler ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalise'])) {
  try {
    Database::query("UPDATE stock_takes SET reconciled='yes' WHERE id = :id", array(':id' => $takeId));
    header('Location: stocktakes.php');   // back to history list
    exit();
  } catch (Exception $exception) {
    $notice = 'Unable to mark this stock-take as reconciled.';
  }
}

/* ---------- Pull variance lines ---------- */
try {
  $lineRows = Database::query(
    "SELECT p.id   AS pid,
        p.sku,
        p.name,
        p.stock              AS theoretical,
        l.counted_qty,
        (l.counted_qty - p.stock) AS variance
     FROM stock_take_lines l
     JOIN products p ON p.id = l.product_id
     WHERE l.stock_take_id = :take
     ORDER BY p.name",
    array(':take' => $takeId)
  )->fetchAll();
} catch (Exception $exception) {
  $notice = 'Unable to load stock-take variance lines.';
  $lineRows = array();
}
?>
<h2>Stock-Take #<?php echo $takeId; ?> – Variance</h2>

<?php if ($notice): ?>
  <p class="notice"><?php echo htmlspecialchars($notice); ?></p>
<?php endif; ?>

<table>
<thead>
  <tr><th>SKU</th><th>Name</th><th>Theoretical</th><th>Counted</th><th>Δ</th><th>Action</th></tr>
</thead>
<tbody>
<?php
$outstanding = 0;
foreach ($lineRows as $r):
  $absVar = abs($r['variance']);
  $rowStyle = ($absVar > 10) ? "style='background:#ffecec;color:#a00;'" : "";
  if ($r['variance'] != 0) $outstanding++;
?>
  <tr <?php echo $rowStyle; ?>>
    <td><?php echo $r['sku']; ?></td>
    <td><?php echo htmlspecialchars($r['name']); ?></td>
    <td><?php echo $r['theoretical']; ?></td>
    <td><?php echo $r['counted_qty']; ?></td>
    <td><?php echo $r['variance']; ?></td>
    <td>
      <?php if ($r['variance'] != 0): ?>
        <a href="adjustment_add.php?pid=<?php echo $r['pid']; ?>&delta=<?php echo $r['variance']; ?>&ref_id=<?php echo $takeId; ?>">
          Post Adjustment
        </a>
      <?php else: ?>
        —
      <?php endif; ?>
    </td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>

<!-- Final-approve form -->
<form method="post" style="margin-top:1rem;">
  <input type="hidden" name="finalise" value="1">
  <input type="submit"
         value="Mark Reconciled"
         <?php echo ($outstanding ? 'disabled style="opacity:.5;"' : ''); ?>>
  <?php if ($outstanding): ?>
    <span style="color:#a00; margin-left:.5rem;">
      <?php echo $outstanding; ?> outstanding adjustment(s) remaining
    </span>
  <?php endif; ?>
</form>

<p>
  <a href="stocktake_new.php">← New Stock-Take</a> |
  <a href="dashboard.php">Dashboard</a>
</p>
<?php include 'includes/footer.php'; ?>
