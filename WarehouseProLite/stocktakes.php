<?php
include 'includes/db.php';
include 'includes/header.php';

/* -------------------------------------------------
   Stock-take list (JOIN + GROUP BY)
------------------------------------------------- */
$sql = "
    SELECT  t.id,
            t.taken_at,
            t.reconciled,
            COUNT(l.id) AS line_count
    FROM    stock_takes t
    LEFT JOIN stock_take_lines l ON l.stock_take_id = t.id
    GROUP BY t.id, t.taken_at, t.reconciled
    ORDER BY t.taken_at DESC
";
$takes = mysql_query($sql);
if (!$takes) {
    die('<p class=\"notice\">Query failed: '.mysql_error().'</p>');
}
?>
<h2>Stock-Take History</h2>

<table>
  <thead>
    <tr>
      <th>ID</th><th>Date/Time</th><th># Counted&nbsp;SKUs</th>
      <th>Status</th><th>View</th>
    </tr>
  </thead>
  <tbody>
  <?php if (mysql_num_rows($takes) === 0): ?>
      <tr><td colspan="5">No stock-takes recorded.</td></tr>
  <?php else: while ($row = mysql_fetch_assoc($takes)): ?>
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
  <?php endwhile; endif; ?>
  </tbody>
</table>

<p>
  <a href="stocktake_new.php">Start New Stock-Take</a> |
  <a href="dashboard.php">Dashboard</a>
</p>
<?php include 'includes/footer.php'; ?>
