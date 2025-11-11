<?php
/* qa_edit.php – update a QA sample */
include 'includes/db.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ---------- UPDATE on POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid   = (int)$_POST['product_id'];
    $brix  = $_POST['brix'] !== '' ? (float)$_POST['brix'] : 'NULL';
    $temp  = $_POST['temperature'] !== '' ? (float)$_POST['temperature'] : 'NULL';
    $pass  = mysql_real_escape_string($_POST['passed']);
    $note  = mysql_real_escape_string($_POST['note']);

    mysql_query("
        UPDATE qa_samples
        SET product_id  = $pid,
            brix        = $brix,
            temperature = $temp,
            passed      = '$pass',
            note        = '$note'
        WHERE id = $id
    ");

    header('Location: qa_samples.php?msg=updated');
    exit();
}

/* ---------- Load existing row ---------- */
$row = mysql_fetch_assoc(mysql_query("
    SELECT q.*, p.sku, p.name
    FROM qa_samples q
    JOIN products p ON p.id = q.product_id
    WHERE q.id = $id
"));
if (!$row) {
    echo "<p class='notice'>QA sample not found.</p>";
    include 'includes/footer.php';
    exit();
}

/* Products for dropdown */
$prods = mysql_query("SELECT id, sku, name FROM products ORDER BY name");
?>
<h2>Edit QA Sample #<?php echo $id; ?></h2>

<form action="qa_edit.php?id=<?php echo $id; ?>" method="post">
    <label>Product
        <select name="product_id" required>
            <?php while ($p = mysql_fetch_assoc($prods)): ?>
                <option value="<?php echo $p['id']; ?>"
                    <?php if ($p['id'] == $row['product_id']) echo 'selected'; ?>>
                    <?php echo $p['sku'].' - '.htmlspecialchars($p['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </label>

    <label>Brix
        <input type="number" step="0.1" name="brix"
               value="<?php echo $row['brix']; ?>">
    </label>

    <label>Temperature °C
        <input type="number" step="0.1" name="temperature"
               value="<?php echo $row['temperature']; ?>">
    </label>

    <label>Status
        <select name="passed">
            <option value="yes"     <?php if ($row['passed']=='yes')     echo 'selected'; ?>>Yes</option>
            <option value="no"      <?php if ($row['passed']=='no')      echo 'selected'; ?>>No</option>
            <option value="pending" <?php if ($row['passed']=='pending') echo 'selected'; ?>>Pending</option>
        </select>
    </label>

    <label>Note
        <textarea name="note" rows="3"><?php echo htmlspecialchars($row['note']); ?></textarea>
    </label>

    <p>
        <input type="submit" value="Save Changes">
        <a href="qa_samples.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
