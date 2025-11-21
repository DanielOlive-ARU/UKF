<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* -------------------------------------------------
   Stock-take list (JOIN + GROUP BY)
------------------------------------------------- */
$notice = '';
try {
    $takes = Database::query(
        "SELECT  t.id,
                 t.taken_at,
                 t.reconciled,
                 COUNT(l.id) AS line_count
         FROM    stock_takes t
         LEFT JOIN stock_take_lines l ON l.stock_take_id = t.id
         GROUP BY t.id, t.taken_at, t.reconciled
         ORDER BY t.taken_at DESC"
    )->fetchAll();
} catch (Exception $exception) {
    $notice = 'Unable to load stock-take history right now.';
    $takes = array();
}
?>
<h2>Stock-Take History</h2>

<?php if ($notice): ?>
    <p class="notice"><?php echo htmlspecialchars($notice); ?></p>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>ID</th><th>Date/Time</th><th># Counted&nbsp;SKUs</th>
      <th>Status</th><th>View</th>
    </tr>
  </thead>
  <tbody>
  <?php if (!$takes): ?>
      <tr><td colspan="5">No stock-takes recorded.</td></tr>
  <?php else: foreach ($takes as $row): ?>
      <?php
        $statusIcon = ($row['reconciled'] === 'yes')
            ? '<span style="color:#2e8b57;">&#10004;</span>'   // green check
            : '<span style="color:#d97b00;">&#9888;</span>';    // amber warning
      ?>
      <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo $row['taken_at']; ?></td>
        <td><?php echo $row['line_count']; ?></td>
        <td><?php echo $statusIcon.' '.$row['reconciled']; ?></td>
        <td><a href="stocktake_view.php?id=<?php echo $row['id']; ?>">Variance</a></td>
      </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>

<p>
  <a href="stocktake_new.php">Start New Stock-Take</a> |
  <a href="dashboard.php">Dashboard</a>
</p>
<?php include 'includes/footer.php'; ?>
