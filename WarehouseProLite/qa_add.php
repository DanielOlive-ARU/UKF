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

/* ---------- Validation ranges ---------- */
$BRIX_MIN = 0;
$BRIX_MAX = 30;
$TEMP_MIN = -10;
$TEMP_MAX = 50;
$NOTE_MAX_LEN = 500;

/* ---------- INSERT on POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'wh_qa_add')) {
        $notice = 'Session expired. Please resubmit the form.';
    } else {
        $formData['product_id'] = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $rawBrix = isset($_POST['brix']) ? trim($_POST['brix']) : '';
        $rawTemp = isset($_POST['temperature']) ? trim($_POST['temperature']) : '';
        $formData['passed'] = isset($_POST['passed']) ? $_POST['passed'] : 'pending';
        $formData['note'] = isset($_POST['note']) ? trim($_POST['note']) : '';

        $productId = $formData['product_id'];
        $brix = null;
        $temperature = null;

        /* Validate Brix */
        if ($rawBrix !== '') {
            if (!is_numeric($rawBrix)) {
                $notice = 'Brix must be a valid number.';
            } else {
                $brixVal = (float)$rawBrix;
                if ($brixVal < $BRIX_MIN || $brixVal > $BRIX_MAX) {
                    $notice = "Brix must be between $BRIX_MIN and $BRIX_MAX.";
                } else {
                    $brix = round($brixVal, 2);
                    $formData['brix'] = number_format($brix, 2, '.', '');
                }
            }
        }

        /* Validate Temperature */
        if (!$notice && $rawTemp !== '') {
            if (!is_numeric($rawTemp)) {
                $notice = 'Temperature must be a valid number.';
            } else {
                $tempVal = (float)$rawTemp;
                if ($tempVal < $TEMP_MIN || $tempVal > $TEMP_MAX) {
                    $notice = "Temperature must be between $TEMP_MIN and $TEMP_MAX °C.";
                } else {
                    $temperature = round($tempVal, 2);
                    $formData['temperature'] = number_format($temperature, 2, '.', '');
                }
            }
        }

        /* Validate Status */
        $passed = in_array($formData['passed'], $statusOptions, true) ? $formData['passed'] : 'pending';

        /* Validate Note length */
        if (!$notice && strlen($formData['note']) > $NOTE_MAX_LEN) {
            $notice = "Note must not exceed $NOTE_MAX_LEN characters.";
        }

        /* Validate Product selection */
        if (!$notice && $productId <= 0) {
            $notice = 'Select a product before saving.';
        }

        /* Insert if all validations pass */
        if (!$notice) {
            $note = $formData['note'];
            $techId = isset($_SESSION['wh_user_id']) ? (int)$_SESSION['wh_user_id'] : null;

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
}

/* ---------- Build product dropdown ---------- */
$prods = Database::query("SELECT id, sku, name FROM products ORDER BY name")->fetchAll();
?>
<h2>Add QA Sample</h2>

<?php if ($notice): ?>
    <p class="notice"><?php echo htmlspecialchars($notice); ?></p>
<?php endif; ?>

<form action="qa_add.php" method="post">
    <?php echo Csrf::field('wh_qa_add'); ?>
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

    <label>Brix (<?php echo $BRIX_MIN; ?>–<?php echo $BRIX_MAX; ?>)
        <input type="number" step="0.01" min="<?php echo $BRIX_MIN; ?>" max="<?php echo $BRIX_MAX; ?>" 
               name="brix" value="<?php echo htmlspecialchars($formData['brix']); ?>">
    </label>

    <label>Temperature °C (<?php echo $TEMP_MIN; ?>–<?php echo $TEMP_MAX; ?>)
        <input type="number" step="0.01" min="<?php echo $TEMP_MIN; ?>" max="<?php echo $TEMP_MAX; ?>" 
               name="temperature" value="<?php echo htmlspecialchars($formData['temperature']); ?>">
    </label>

    <label>Status
        <select name="passed">
            <option value="yes" <?php if ($formData['passed']==='yes') echo 'selected'; ?>>Yes</option>
            <option value="no" <?php if ($formData['passed']==='no') echo 'selected'; ?>>No</option>
            <option value="pending" <?php if ($formData['passed']==='pending') echo 'selected'; ?>>Pending</option>
        </select>
    </label>

    <label>Note (max <?php echo $NOTE_MAX_LEN; ?> characters)
        <textarea name="note" rows="3" maxlength="<?php echo $NOTE_MAX_LEN; ?>"><?php echo htmlspecialchars($formData['note']); ?></textarea>
    </label>

    <p>
        <input type="submit" value="Save Sample">
        <a href="qa_samples.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
