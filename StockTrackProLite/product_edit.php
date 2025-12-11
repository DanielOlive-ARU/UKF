<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ---------- save ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'stock_product_edit')) {
        header('Location: products.php?msg=csrf');
        exit();
    }

    $sku               = trim($_POST['sku']);
    $name              = trim($_POST['name']);
    $cat               = ($_POST['category_id'] === '' ? null : (int)$_POST['category_id']);
    $price             = (float)$_POST['price'];
    $stock             = (int)$_POST['stock'];
    $country_iso       = strtoupper(trim($_POST['country_iso']));
    $class             = $_POST['class'];
    $pack_uom          = $_POST['pack_uom'];
    $default_pack_weight_g = ($pack_uom === 'g' && $_POST['default_pack_weight_g'] !== '') 
                             ? (int)$_POST['default_pack_weight_g'] 
                             : null;
    $best_before_days  = (int)$_POST['best_before_days'];

    // Server-side validation: pack weight required when UOM is grams
    if ($pack_uom === 'g' && $default_pack_weight_g === null) {
        echo "<p class='notice'>Pack weight is required when unit of measure is grams.</p>";
    } else {
        Database::query(
            "UPDATE products SET
                sku = :sku,
                name = :name,
                category_id = :category_id,
                price = :price,
                stock = :stock,
                country_iso = :country_iso,
                class = :class,
                pack_uom = :pack_uom,
                default_pack_weight_g = :default_pack_weight_g,
                best_before_days = :best_before_days
             WHERE id = :id",
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
                ':best_before_days' => $best_before_days,
                ':id' => $id
            )
        );

        header('Location: products.php?msg=updated');
        exit();
    }
}

/* load row */
$row = Database::fetchOne("SELECT * FROM products WHERE id = :id", array(':id' => $id));
if (!$row) { echo "<p class='notice'>Product not found.</p>"; include 'includes/footer.php'; exit; }

/* categories for dropdown */
$cats = Database::query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
?>
<h2>Edit Product</h2>

<form action="product_edit.php?id=<?php echo $id; ?>" method="post">
    <?php echo Csrf::field('stock_product_edit'); ?>
    <label>SKU
        <input type="text" name="sku" value="<?php echo htmlspecialchars($row['sku']); ?>" required>
    </label>

    <label>Name
        <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
    </label>

    <label>Category
        <select name="category_id">
            <option value="">- none -</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?php echo $c['id']; ?>"
                    <?php if ($c['id']==$row['category_id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Price (£)
        <input type="number" step="0.01" name="price"
               value="<?php echo number_format($row['price'],2,'.',''); ?>" required>
    </label>

    <label>Stock
        <input type="number" name="stock" value="<?php echo $row['stock']; ?>" required>
    </label>

    <label>Country of Origin <small>(ISO 3166-2 Country Code)</small>
        <input type="text" name="country_iso" required maxlength="2"
               value="<?php echo htmlspecialchars($row['country_iso']); ?>"
               pattern="[A-Za-z]{2}" style="text-transform:uppercase"
               title="Enter a 2-letter ISO 3166-2 country code, e.g. GB, ES, ZA.">
    </label>

    <label>Class
        <select name="class" required title="Select the produce grade classification.">
            <option value="">- select -</option>
            <option value="X" <?php if ($row['class']==='X') echo 'selected'; ?>>X (Extra)</option>
            <option value="I" <?php if ($row['class']==='I') echo 'selected'; ?>>I</option>
            <option value="II" <?php if ($row['class']==='II') echo 'selected'; ?>>II</option>
        </select>
    </label>

    <label>Pack Unit of Measure
        <select name="pack_uom" id="pack_uom" required title="Select how this product is packaged.">
            <option value="each" <?php if ($row['pack_uom']==='each') echo 'selected'; ?>>Each</option>
            <option value="g" <?php if ($row['pack_uom']==='g') echo 'selected'; ?>>Grams (g)</option>
            <option value="varies" <?php if ($row['pack_uom']==='varies') echo 'selected'; ?>>Varies</option>
        </select>
    </label>

    <div id="weight-field" style="display:none;">
        <label>Pack Weight (g)
            <input type="number" name="default_pack_weight_g" id="default_pack_weight_g" min="1"
                   value="<?php echo $row['default_pack_weight_g'] !== null ? $row['default_pack_weight_g'] : ''; ?>"
                   title="Enter the pack weight in grams for pre-packaged fruit.">
        </label>
    </div>

    <label>Best Before Days <small>(after shipping)</small>
        <input type="number" name="best_before_days" required min="1"
               value="<?php echo $row['best_before_days']; ?>"
               title="Enter the number of days until best-before after shipping.">
    </label>

    <p>
        <input type="submit" value="Save">
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
