<?php
/* adjustment_add.php – create +/- stock adjustment
   Optional query params:
   ?pid=11&delta=-20   → pre-fill product and qty_delta
*/
include 'includes/db.php';
include 'includes/header.php';

/* --------- Save on POST --------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid   = (int)$_POST['product_id'];
    $delta = (int)$_POST['qty_delta'];
    $reason= mysql_real_escape_string($_POST['reason']);

    /* approved_by could be the logged-in user’s ID; using 0 for legacy demo */
    $uid   = isset($_SESSION['wh_user_id']) ? (int)$_SESSION['wh_user_id'] : 'NULL';

    /* optional ref back to a stock-take id */
    $refId = isset($_POST['ref_id']) ? (int)$_POST['ref_id'] : 0;

    mysql_query("
        INSERT INTO adjustments (product_id, qty_delta, reason, approved_by, created_at, ref_id)
        VALUES ($pid, $delta, '$reason', $uid, NOW(), $refId)
    ");
    
        // apply the adjustment to the product stock so the variance seen on
        // the originating stock-take will reflect the posted adjustment
        if ($delta != 0) {
            mysql_query("UPDATE products SET stock = stock + ($delta) WHERE id = $pid");
        }
    
        if ($refId) {
            header('Location: stocktake_view.php?id=' . $refId);
        } else {
            header('Location: adjustments.php?msg=added');
        }
    exit();
}

/* ---------- Load products for drop-down ---------- */
$prods = mysql_query("SELECT id, sku, name FROM products ORDER BY name");

/* Pre-fill fields if called from stock-take variance link */
$prefillPid   = isset($_GET['pid'])   ? (int)$_GET['pid']   : '';
$prefillDelta = isset($_GET['delta']) ? (int)$_GET['delta'] : '';
$prefillRefId = isset($_GET['ref_id']) ? (int)$_GET['ref_id'] : 0;
?>
<h2>Add Adjustment</h2>

<form action="adjustment_add.php" method="post">
    <label>Product
        <select name="product_id" required>
            <option value="">-- select --</option>
            <?php while ($p = mysql_fetch_assoc($prods)): ?>
                <option value="<?php echo $p['id']; ?>"
                    <?php if ($p['id'] == $prefillPid) echo 'selected'; ?>>
                    <?php echo $p['sku'].' - '.htmlspecialchars($p['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </label>

    <label>Quantity Δ (e.g., -3 or 5)
        <input type="number" name="qty_delta"
               value="<?php echo $prefillDelta; ?>" required>
    </label>

    <label>Reason
        <select name="reason" required>
            <option value="damage">Damage</option>
            <option value="writeoff">Write-Off</option>
            <option value="correction" selected>Correction</option>
            <option value="qa_sample">QA Sample</option>   <!-- NEW -->
        </select>
    </label>


 <!-- pass the ref_id through the form so POST handler can redirect back -->
    <?php if ($prefillRefId): ?>
      <input type="hidden" name="ref_id" value="<?php echo $prefillRefId; ?>">
    <?php endif; ?>

    <p>
        <input type="submit" value="Save">
        <a href="stocktake_view.php?id=<?php echo $prefillRefId; ?>">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
