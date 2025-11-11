<?php
/* qa_add.php – log a new QA sample */
include 'includes/db.php';
include 'includes/header.php';

/* ---------- INSERT on POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pid  = (int)$_POST['product_id'];
    $brix = ($_POST['brix'] !== '') ? (float)$_POST['brix'] : 'NULL';
    $temp = ($_POST['temperature'] !== '') ? (float)$_POST['temperature'] : 'NULL';
    $pass = mysql_real_escape_string($_POST['passed']);
    $note = mysql_real_escape_string($_POST['note']);

    /* get current user ID or NULL */
    $tech = isset($_SESSION['wh_user_id']) ? (int)$_SESSION['wh_user_id'] : 'NULL';

    $ok = mysql_query("
        INSERT INTO qa_samples
            (product_id, sample_time, brix, temperature, passed, tech_id, note)
        VALUES
            ($pid, NOW(), $brix, $temp, '$pass', $tech, '$note')
    ");

    if (!$ok) {
        die('<p class="notice">Insert failed: '.mysql_error().'</p>');
    }

    header('Location: qa_samples.php?msg=added');
    exit();
}

/* ---------- Build product dropdown ---------- */
$prods = mysql_query("SELECT id, sku, name FROM products ORDER BY name");
?>
<h2>Add QA Sample</h2>

<form action="qa_add.php" method="post">
    <label>Product
        <select name="product_id" required>
            <option value="">-- select --</option>
            <?php while ($p = mysql_fetch_assoc($prods)): ?>
                <option value="<?php echo $p['id']; ?>">
                    <?php echo $p['sku'].' - '.htmlspecialchars($p['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </label>

    <label>Brix
        <input type="number" step="0.1" name="brix">
    </label>

    <label>Temperature °C
        <input type="number" step="0.1" name="temperature">
    </label>

    <label>Status
        <select name="passed">
            <option value="yes">Yes</option>
            <option value="no">No</option>
            <option value="pending" selected>Pending</option>
        </select>
    </label>

    <label>Note
        <textarea name="note" rows="3"></textarea>
    </label>

    <p>
        <input type="submit" value="Save Sample">
        <a href="qa_samples.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
