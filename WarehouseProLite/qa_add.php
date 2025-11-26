<?php
/* qa_add.php – log a new QA sample */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

$notice = '';
$formData = array(
    'product_id' => '',
    'brix' => '',
    'temperature' => '',
    'passed' => 'pending',
    'note' => ''
);
$statusOptions = array('yes', 'no', 'pending');

/* ---------- INSERT on POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['product_id'] = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $rawBrix = isset($_POST['brix']) ? trim($_POST['brix']) : '';
    $rawTemp = isset($_POST['temperature']) ? trim($_POST['temperature']) : '';
    $formData['passed'] = isset($_POST['passed']) ? $_POST['passed'] : 'pending';
    $formData['note'] = isset($_POST['note']) ? trim($_POST['note']) : '';

    $productId = $formData['product_id'];
    if ($rawBrix !== '' && is_numeric($rawBrix)) {
        $brix = round((float)$rawBrix, 2);
        $formData['brix'] = number_format($brix, 2, '.', '');
    } else {
        $brix = null;
        $formData['brix'] = '';
    }

    if ($rawTemp !== '' && is_numeric($rawTemp)) {
        $temperature = round((float)$rawTemp, 2);
        $formData['temperature'] = number_format($temperature, 2, '.', '');
    } else {
        $temperature = null;
        $formData['temperature'] = '';
    }

    $passed = in_array($formData['passed'], $statusOptions, true) ? $formData['passed'] : 'pending';
    $note = $formData['note'];
    $techId = isset($_SESSION['wh_user_id']) ? (int)$_SESSION['wh_user_id'] : null;

    if ($productId <= 0) {
        $notice = 'Select a product before saving.';
    } else {
        try {
            Database::query(
                "INSERT INTO qa_samples
                    (product_id, sample_time, brix, temperature, passed, tech_id, note)
                 VALUES
                    (:product_id, NOW(), :brix, :temperature, :passed, :tech_id, :note)",
                array(
                    ':product_id' => $productId,
                    ':brix' => $brix,
                    ':temperature' => $temperature,
                    ':passed' => $passed,
                    ':tech_id' => $techId,
                    ':note' => $note
                )
            );

            header('Location: qa_samples.php?msg=added');
            exit();
        } catch (Exception $exception) {
            $notice = 'QA sample could not be saved. Please try again.';
        }
    }
}

/* ---------- Build product dropdown ---------- */
$prods = Database::query("SELECT id, sku, name FROM products ORDER BY name")->fetchAll();
?>
<h2>Add QA Sample</h2>

<?php if ($notice): ?>
    <p class="notice"><?php echo htmlspecialchars($notice); ?></p>
<?php endif; ?>

<form action="qa_add.php" method="post">
    <label>Product
        <select name="product_id" required>
            <option value="">-- select --</option>
            <?php foreach ($prods as $p): ?>
                <option value="<?php echo $p['id']; ?>" <?php if ($formData['product_id'] == $p['id']) echo 'selected'; ?>>
                    <?php echo $p['sku'].' - '.htmlspecialchars($p['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Brix
        <input type="number" step="0.01" name="brix" value="<?php echo htmlspecialchars($formData['brix']); ?>">
    </label>

    <label>Temperature °C
        <input type="number" step="0.01" name="temperature" value="<?php echo htmlspecialchars($formData['temperature']); ?>">
    </label>

    <label>Status
        <select name="passed">
            <option value="yes" <?php if ($formData['passed']==='yes') echo 'selected'; ?>>Yes</option>
            <option value="no" <?php if ($formData['passed']==='no') echo 'selected'; ?>>No</option>
            <option value="pending" <?php if ($formData['passed']==='pending') echo 'selected'; ?>>Pending</option>
        </select>
    </label>

    <label>Note
        <textarea name="note" rows="3"><?php echo htmlspecialchars($formData['note']); ?></textarea>
    </label>

    <p>
        <input type="submit" value="Save Sample">
        <a href="qa_samples.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
