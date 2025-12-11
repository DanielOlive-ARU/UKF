<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* ---------- Handle INSERT ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'stock_product_add')) {
        header('Location: products.php?msg=csrf');
        exit();
    }

    $sku               = trim($_POST['sku']);
    $name              = trim($_POST['name']);
    $cat               = ($_POST['category_id'] === '' ? null : (int)$_POST['category_id']);
    $price_raw         = trim($_POST['price']);
    $price             = (float)$price_raw;
    $stock             = (int)$_POST['stock'];
    $country_iso       = strtoupper(trim($_POST['country_iso']));
    $class             = $_POST['class'];
    $pack_uom          = $_POST['pack_uom'];
    $default_pack_weight_g = ($pack_uom === 'g' && $_POST['default_pack_weight_g'] !== '') 
                             ? (int)$_POST['default_pack_weight_g'] 
                             : null;
    $best_before_days  = (int)$_POST['best_before_days'];

    // Server-side validation
    $errors = array();

    // Check SKU uniqueness
    $existing = Database::fetchOne(
        "SELECT id FROM products WHERE sku = :sku",
        array(':sku' => $sku)
    );
    if ($existing) {
        $errors[] = 'SKU already exists. Please use a unique SKU.';
    }

    // Validate price format (X.XX or XX.XX)
    if (!preg_match('/^\d{1,2}\.\d{2}$/', $price_raw)) {
        $errors[] = 'Price must be in format X.XX or XX.XX (e.g. 1.30, 0.20, 10.10).';
    }

    // Pack weight required when UOM is grams
    if ($pack_uom === 'g' && $default_pack_weight_g === null) {
        $errors[] = 'Pack weight is required when unit of measure is grams.';
    }

    if (!empty($errors)) {
        foreach ($errors as $err) {
            echo "<p class='notice'>" . htmlspecialchars($err) . "</p>";
        }
    } else {
        Database::query(
            "INSERT INTO products (sku, name, category_id, price, stock, country_iso, class, pack_uom, default_pack_weight_g, best_before_days)
             VALUES (:sku, :name, :category_id, :price, :stock, :country_iso, :class, :pack_uom, :default_pack_weight_g, :best_before_days)",
            array(
                ':sku' => $sku,
                ':name' => $name,
                ':category_id' => $cat,
                ':price' => $price,
                ':stock' => $stock,
                ':country_iso' => $country_iso,
                ':class' => $class,
                ':pack_uom' => $pack_uom,
                ':default_pack_weight_g' => $default_pack_weight_g,
                ':best_before_days' => $best_before_days
            )
        );

        header('Location: products.php?msg=added');
        exit();
    }
}

/* ------- Load categories for drop-down ------- */
$cats = Database::query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
?>
<h2>Add Product</h2>
<!-- Formats SKU user input to force a specific pattern -->
<form action="add_product.php" method="post">
    <?php echo Csrf::field('stock_product_add'); ?>
    <label>SKU
        <input type="text" name="sku" required
               pattern="[A-Z]{2}[._%+\-][A-Z]{3}[._%+\-][0-9]{3,}"
               title="Format: AA-XAAA-X### (uppercase, separators ., _, %, +, -)">
    </label>

    <label>Name
        <input type="text" name="name" required>
    </label>

    <label>Category
        <select name="category_id">
            <option value="">- none -</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?php echo $c['id']; ?>">
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Price (£)
        <input type="text" name="price" required
               pattern="\d{1,2}\.\d{2}"
               title="Price must be in format X.XX or XX.XX (e.g. 1.30, 0.20, 10.10)">
    </label>

    <label>Stock
        <input type="number" name="stock" value="0" required>
    </label>

    <label>Country of Origin <small>(ISO 3166-2 Country Code)</small>
        <input type="text" name="country_iso" required maxlength="2"
               pattern="[A-Za-z]{2}" style="text-transform:uppercase"
               title="Enter a 2-letter ISO 3166-2 country code, e.g. GB, ES, ZA.">
    </label>

    <label>Class
        <select name="class" required title="Select the produce grade classification.">
            <option value="">- select -</option>
            <option value="X">X (Extra)</option>
            <option value="I">I</option>
            <option value="II">II</option>
        </select>
    </label>

    <label>Pack Unit of Measure
        <select name="pack_uom" id="pack_uom" required title="Select how this product is packaged.">
            <option value="each">Each</option>
            <option value="g">Grams (g)</option>
            <option value="varies">Varies</option>
        </select>
    </label>

    <div id="weight-field" style="display:none;">
        <label>Pack Weight (g)
            <input type="number" name="default_pack_weight_g" id="default_pack_weight_g" min="1"
                   title="Enter the pack weight in grams for pre-packaged fruit.">
        </label>
    </div>

    <label>Best Before Days <small>(after shipping)</small>
        <input type="number" name="best_before_days" required min="1"
               title="Enter the number of days until best-before after shipping.">
    </label>

    <p>
        <input type="submit" value="Add Product">
        <a href="products.php">Cancel</a>
    </p>
</form>

<script>
$(function() {
    function toggleWeightField() {
        var uom = $('#pack_uom').val();
        if (uom === 'g') {
            $('#weight-field').show();
            $('#default_pack_weight_g').prop('required', true);
        } else {
            $('#weight-field').hide();
            $('#default_pack_weight_g').prop('required', false).val('');
        }
    }
    $('#pack_uom').on('change', toggleWeightField);
    toggleWeightField();
});
</script>

<?php include 'includes/footer.php'; ?>
