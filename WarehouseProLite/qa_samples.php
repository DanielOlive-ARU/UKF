<?php
include 'includes/db.php';
include 'includes/header.php';

/* ---------- flash notice ---------- */
$flash = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added')   $flash = '<p class="notice">QA sample added.</p>';
    if ($_GET['msg'] === 'updated') $flash = '<p class="notice">QA sample updated.</p>';
    if ($_GET['msg'] === 'deleted') $flash = '<p class="notice">QA sample deleted.</p>';
}

/* ---------- fetch samples (latest first) ---------- */
$samples = mysql_query("
    SELECT q.id,
           q.sample_time,
           p.sku,
           p.name,
           q.brix,
           q.temperature,
           q.passed
    FROM qa_samples q
    JOIN products p ON p.id = q.product_id
    ORDER BY q.sample_time DESC
");
?>
<h2>QA Samples</h2>
<?php echo $flash; ?>

<p><a href="qa_add.php" class="btn">+ Add Sample</a></p>

<table>
  <thead>
    <tr>
      <th>ID</th><th>Date/Time</th><th>SKU</th><th>Name</th>
      <th>Brix</th><th>Temp&nbsp;°C</th><th>Status</th><th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php if (mysql_num_rows($samples) === 0): ?>
      <tr><td colspan="8">No QA samples recorded.</td></tr>
  <?php else: while ($r = mysql_fetch_assoc($samples)): ?>
      <?php
        /* highlight fails */
        $rowStyle = ($r['passed'] === 'no') ? "style='background:#ffecec;color:#a00;'" : "";
        $status   = ucfirst($r['passed']);
      ?>
      <tr <?php echo $rowStyle; ?>>
        <td><?php echo $r['id']; ?></td>
        <td><?php echo $r['sample_time']; ?></td>
        <td><?php echo $r['sku']; ?></td>
        <td><?php echo htmlspecialchars($r['name']); ?></td>
        <td><?php echo $r['brix']; ?></td>
        <td><?php echo $r['temperature']; ?></td>
        <td><?php echo $status; ?></td>
        <td>
          <a href="qa_edit.php?id=<?php echo $r['id']; ?>">Edit</a> |
          <a href="qa_delete.php?id=<?php echo $r['id']; ?>"
             onclick="return confirm('Delete this QA sample?');">Delete</a>
        </td>
      </tr>
  <?php endwhile; endif; ?>
  </tbody>
</table>

<?php include 'includes/footer.php'; ?>
