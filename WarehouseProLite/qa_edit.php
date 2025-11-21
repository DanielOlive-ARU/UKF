<?php
/* qa_edit.php – update a QA sample */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$notice = '';
$statusOptions = array('yes', 'no', 'pending');

if ($id <= 0) {
    echo "<p class='notice'>QA sample not found.</p>";
    include 'includes/footer.php';
    exit();
}

$sample = Database::fetchOne(
    "SELECT q.*, p.sku, p.name
     FROM qa_samples q
     JOIN products p ON p.id = q.product_id
     WHERE q.id = :id",
    array(':id' => $id)
);

if (!$sample) {
    echo "<p class='notice'>QA sample not found.</p>";
    include 'includes/footer.php';
    exit();
}

$formData = array(
    'product_id' => (int)$sample['product_id'],
    'brix' => $sample['brix'] !== null ? number_format((float)$sample['brix'], 2, '.', '') : '',
    'temperature' => $sample['temperature'] !== null ? number_format((float)$sample['temperature'], 2, '.', '') : '',
    'passed' => $sample['passed'],
    'note' => $sample['note']
);

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

    if ($productId <= 0) {
        $notice = 'Select a product before saving.';
    } else {
        try {
            Database::query(
                "UPDATE qa_samples
                 SET product_id = :product_id,
                     brix = :brix,
                     temperature = :temperature,
                     passed = :passed,
                     note = :note
                 WHERE id = :id",
                array(
                    ':product_id' => $productId,
                    ':brix' => $brix,
                    ':temperature' => $temperature,
                    ':passed' => $passed,
                    ':note' => $note,
                    ':id' => $id
                )
            );

            header('Location: qa_samples.php?msg=updated');
            exit();
        } catch (Exception $exception) {
            $notice = 'QA sample could not be updated. Please try again.';
        }
    }
}

$prods = Database::query("SELECT id, sku, name FROM products ORDER BY name")->fetchAll();
?>
<h2>Edit QA Sample #<?php echo $id; ?></h2>

<?php if ($notice): ?>
    <p class="notice"><?php echo htmlspecialchars($notice); ?></p>
<?php endif; ?>

<form action="qa_edit.php?id=<?php echo $id; ?>" method="post">
    <label>Product
        <select name="product_id" required>
            <?php foreach ($prods as $p): ?>
                <option value="<?php echo $p['id']; ?>" <?php if ($p['id'] == $formData['product_id']) echo 'selected'; ?>>
                    <?php echo $p['sku'].' - '.htmlspecialchars($p['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Brix
        <input type="number" step="0.01" name="brix"
               value="<?php echo htmlspecialchars($formData['brix']); ?>">
    </label>

    <label>Temperature °C
        <input type="number" step="0.01" name="temperature"
               value="<?php echo htmlspecialchars($formData['temperature']); ?>">
    </label>

    <label>Status
        <select name="passed">
            <option value="yes"     <?php if ($formData['passed']=='yes')     echo 'selected'; ?>>Yes</option>
            <option value="no"      <?php if ($formData['passed']=='no')      echo 'selected'; ?>>No</option>
            <option value="pending" <?php if ($formData['passed']=='pending') echo 'selected'; ?>>Pending</option>
        </select>
    </label>

    <label>Note
        <textarea name="note" rows="3"><?php echo htmlspecialchars($formData['note']); ?></textarea>
    </label>

    <p>
        <input type="submit" value="Save Changes">
        <a href="qa_samples.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
