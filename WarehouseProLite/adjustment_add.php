<?php
/* adjustment_add.php – create +/- stock adjustment
   Optional query params:
   ?pid=11&delta=-20   → pre-fill product and qty_delta
*/
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

$notice = '';
$supportsRefId = false;

try {
    $supportsRefId = (bool)Database::query("SHOW COLUMNS FROM adjustments LIKE 'ref_id'")->fetch();
} catch (Exception $schemaException) {
    // fallback to schema without ref_id
    $supportsRefId = false;
}

/* --------- Save on POST --------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid   = (int)$_POST['product_id'];
    $delta = (int)$_POST['qty_delta'];
    $reason= trim($_POST['reason']);

    $uid   = isset($_SESSION['wh_user_id']) ? (int)$_SESSION['wh_user_id'] : null;
    $refId = isset($_POST['ref_id']) ? (int)$_POST['ref_id'] : 0;

    try {
        Database::transaction(function () use ($pid, $delta, $reason, $uid, $refId, $supportsRefId) {
            $columns = 'product_id, qty_delta, reason, approved_by, created_at';
            $values  = ':product_id, :qty_delta, :reason, :approved_by, NOW()';
            $params  = array(
                ':product_id' => $pid,
                ':qty_delta' => $delta,
                ':reason' => $reason,
                ':approved_by' => $uid
            );

            if ($supportsRefId) {
                $columns .= ', ref_id';
                $values  .= ', :ref_id';
                $params[':ref_id'] = $refId ?: null;
            }

            Database::query(
                "INSERT INTO adjustments ($columns) VALUES ($values)",
                $params
            );

            if ($delta !== 0) {
                Database::query(
                    "UPDATE products SET stock = stock + :delta WHERE id = :id",
                    array(':delta' => $delta, ':id' => $pid)
                );
            }
        });

        if ($refId) {
            header('Location: stocktake_view.php?id=' . $refId);
        } else {
            header('Location: adjustments.php?msg=added');
        }
        exit();
    } catch (Exception $exception) {
        $notice = 'Adjustment could not be saved. Please try again.';
    }
}

/* ---------- Load products for drop-down ---------- */
$prods = Database::query("SELECT id, sku, name FROM products ORDER BY name")->fetchAll();

/* Pre-fill fields if called from stock-take variance link */
$prefillPid   = isset($_GET['pid'])   ? (int)$_GET['pid']   : '';
$prefillDelta = isset($_GET['delta']) ? (int)$_GET['delta'] : '';
$prefillRefId = isset($_GET['ref_id']) ? (int)$_GET['ref_id'] : 0;
?>
<h2>Add Adjustment</h2>

<form action="adjustment_add.php" method="post">
    <?php if ($notice): ?>
        <p class="notice"><?php echo htmlspecialchars($notice); ?></p>
    <?php endif; ?>
    <label>Product
        <select name="product_id" required>
            <option value="">-- select --</option>
            <?php foreach ($prods as $p): ?>
                <option value="<?php echo $p['id']; ?>"
                    <?php if ($p['id'] == $prefillPid) echo 'selected'; ?>>
                    <?php echo $p['sku'].' - '.htmlspecialchars($p['name']); ?>
                </option>
            <?php endforeach; ?>
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
