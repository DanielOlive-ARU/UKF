<?php
/* stocktake_new.php – create stock-take & capture counts */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

$notice = '';
$takeId = isset($_GET['take']) ? (int)$_GET['take'] : 0;

/* ---------- Handle first POST: create stock_take header ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_take'])) {

    /* use real clerk id, or NULL if not stored in session */
    $clerk = isset($_SESSION['wh_user_id']) ? (int)$_SESSION['wh_user_id'] : null;

    try {
        Database::query(
            "INSERT INTO stock_takes (taken_at, clerk_id, note)
             VALUES (NOW(), :clerk_id, '')",
            array(':clerk_id' => $clerk)
        );

        $newTakeId = Database::connection()->lastInsertId();
        header('Location: stocktake_new.php?take=' . $newTakeId);
        exit();
    } catch (Exception $exception) {
        $notice = 'Unable to start a new stock-take. Please try again.';
    }
}


/* ------------ Handle save counts (second POST) ------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_lines'])) {
    $takeId = isset($_POST['take_id']) ? (int)$_POST['take_id'] : 0;
    $counts = isset($_POST['count']) && is_array($_POST['count']) ? $_POST['count'] : array();

    if ($takeId <= 0) {
        $notice = 'Invalid stock-take reference. Please start again.';
    } else {
        try {
            Database::transaction(function () use ($counts, $takeId) {
                foreach ($counts as $pid => $qty) {
                    $productId = (int)$pid;
                    $quantity  = (int)$qty;
                    if ($quantity === 0) {
                        continue;   // skip blanks
                    }
                    Database::query(
                        "INSERT INTO stock_take_lines (stock_take_id, product_id, counted_qty)
                         VALUES (:take_id, :product_id, :qty_insert)
                         ON DUPLICATE KEY UPDATE counted_qty = :qty_update",
                        array(
                            ':take_id' => $takeId,
                            ':product_id' => $productId,
                            ':qty_insert' => $quantity,
                            ':qty_update' => $quantity
                        )
                    );
                }
            });

            header('Location: stocktake_view.php?id=' . $takeId);
            exit();
        } catch (Exception $exception) {
            $notice = 'Counts could not be saved. Please try again.';
        }
    }
}

/* ---------- If no take yet, show “Create” button ---------- */
if (!$takeId):
?>
<h2>New Stock-Take</h2>
<?php if ($notice): ?>
    <p class="notice"><?php echo htmlspecialchars($notice); ?></p>
<?php endif; ?>
<form method="post">
    <p>This will create a new stock-take session for ALL products.</p>
    <input type="hidden" name="create_take" value="1">
    <input type="submit" value="Start Stock-Take">
    <a href="dashboard.php">Cancel</a>
</form>
<?php
include 'includes/footer.php';
exit();
endif;

/* ---------- Show counting form ---------- */
$prods  = Database::query("SELECT id, sku, name, stock FROM products ORDER BY name")->fetchAll();
?>
<h2>Stock-Take #<?php echo $takeId; ?></h2>
<?php if ($notice): ?>
    <p class="notice"><?php echo htmlspecialchars($notice); ?></p>
<?php endif; ?>
<form method="post">
<input type="hidden" name="take_id" value="<?php echo $takeId; ?>">
<table>
<thead><tr><th>SKU</th><th>Name</th><th>Theoretical</th><th>Counted Qty</th></tr></thead>
<tbody>
<?php foreach ($prods as $p): ?>
<tr>
    <td><?php echo $p['sku']; ?></td>
    <td><?php echo htmlspecialchars($p['name']); ?></td>
    <td><?php echo $p['stock']; ?></td>
    <td><input type="number" name="count[<?php echo $p['id']; ?>]" style="width:80px"></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<p>
    <input type="hidden" name="save_lines" value="1">
    <input type="submit" value="Save Counts">
    <a href="stocktake_new.php">Cancel</a>
</p>
</form>
<?php include 'includes/footer.php'; ?>
