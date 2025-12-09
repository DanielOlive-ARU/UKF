<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* join categories so we can show the name */
$stmt = Database::query("
    SELECT p.id, p.sku, p.name, p.price, p.stock,
           IFNULL(c.name,'-') AS category
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.name
");
$products = $stmt->fetchAll();

/*
 * Attempt to match product images from the `assets/` folder.
 * Matching strategy (scored): exact id, exact sku, exact name, contains sku, contains name.
 */
$imageFiles = glob(__DIR__ . '/assets/*.{jpg,jpeg,png,gif,webp,avif}', GLOB_BRACE);
$imageMap = array();
$unmatchedImages = array();

// helper to normalise strings for matching
function _prod_sanitize($s) {
    $s = strtolower((string)$s);
    $s = preg_replace('/[^a-z0-9]+/', '', $s);
    return $s;
}

// precompute file keys
$fileKeys = array();
foreach ($imageFiles as $f) {
    $fname = pathinfo($f, PATHINFO_FILENAME);
    $fileKeys[$f] = _prod_sanitize($fname);
}

// For each product, find best matching file by score
foreach ($products as $p) {
    $bestScore = 0;
    $bestFile = null;

    $idKey = _prod_sanitize($p['id']);
    $skuKey = _prod_sanitize($p['sku']);
    $nameKey = _prod_sanitize($p['name']);

    foreach ($fileKeys as $filePath => $fileKey) {
        $score = 0;

        if ($fileKey === $idKey) {
            $score += 200;
        }
        if ($skuKey !== '' && $fileKey === $skuKey) {
            $score += 150;
        }
        if ($nameKey !== '' && $fileKey === $nameKey) {
            $score += 120;
        }
        if ($skuKey !== '' && strpos($fileKey, $skuKey) !== false) {
            $score += 60;
        }
        if ($nameKey !== '' && strpos($fileKey, $nameKey) !== false) {
            $score += 40;
        }

        // slight bonus for short filenames that match a portion of the name (likely intentional)
        if ($fileKey !== '' && $nameKey !== '' && (strlen($fileKey) > 3) && (strpos($nameKey, $fileKey) !== false)) {
            $score += 10;
        }

        // Avoid assigning a file that's already been assigned to another product unless it scores much higher
        if ($score > 0) {
            // if file already mapped, reduce score unless this is a much better match
            foreach ($imageMap as $assignedPid => $assignedFile) {
                if ($assignedFile === $filePath) {
                    $score = intval($score / 4);
                }
            }
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestFile = $filePath;
        }
    }

    if ($bestFile) {
        $imageMap[(int)$p['id']] = 'assets/' . basename($bestFile);
        // remove from candidates so it won't be used again
        unset($fileKeys[$bestFile]);
        unset($imageFiles[array_search($bestFile, $imageFiles)]);
    }
}



$flash = '';
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':
            $flash = '<p class="notice">Product added.</p>';
            break;
        case 'updated':
            $flash = '<p class="notice">Product updated.</p>';
            break;
        case 'deleted':
            $flash = '<p class="notice">Product deleted.</p>';
            break;
        case 'in_use':
            $flash = '<p class="notice">Product is referenced by other records and cannot be deleted yet.</p>';
            break;
        case 'error':
            $flash = '<p class="notice">Action failed. Please try again.</p>';
            break;
        case 'csrf':
            $flash = '<p class="notice">Session expired. Please resubmit the form.</p>';
            break;
    }
}
?>
<h2>Products</h2>

<?php echo $flash; ?>

<p>
    <a href="add_product.php" class="btn">+ Add Product</a>
</p>

<table>
    <thead>
        <tr>
            <th>Image</th>
            <th>SKU</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price (£)</th>
            <th>Stock</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
<?php if (!$products): ?>
        <tr><td colspan="7">No products yet.</td></tr>
<?php else: foreach ($products as $row): ?>
        <tr>
            <td>
                <?php if (isset($imageMap[(int)$row['id']])): ?>
                    <div style="width:96px;height:72px;overflow:hidden;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.08);display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid #eee;">
                        <img src="<?php echo htmlspecialchars($imageMap[(int)$row['id']]); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
                    </div>
                <?php else: ?>
                    <div style="width:96px;height:72px;border-radius:6px;background:#f8f8f8;border:1px dashed #eee;display:flex;align-items:center;justify-content:center;color:#999;font-size:12px;">No image</div>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($row['sku']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['category']); ?></td>
            <td><?php echo number_format($row['price'], 2); ?></td>
            <td><?php echo (int)$row['stock']; ?></td>
            <td>
                <a href="product_edit.php?id=<?php echo $row['id']; ?>">Edit</a> |
                <form action="product_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this product?');">
                    <?php echo Csrf::field('stock_product_delete'); ?>
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <button type="submit" style="background:none;border:none;color:#06c;padding:0;cursor:pointer;text-decoration:underline;">Delete</button>
                </form>
            </td>
        </tr>
<?php endforeach; endif; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
