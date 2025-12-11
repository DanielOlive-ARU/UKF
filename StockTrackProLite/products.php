<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* join categories so we can show the name */
$stmt = null;

/* Search parameters (replicating Orders page behavior) */
$search_id = isset($_GET['search_id']) ? trim($_GET['search_id']) : '';
$search_sku = isset($_GET['search_sku']) ? trim($_GET['search_sku']) : '';
$search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
$search_category = isset($_GET['search_category']) ? trim($_GET['search_category']) : '';

/* Pagination configuration */
$allowed_per_page = array(10, 20, 50, 100);
$default_per_page = 20;
$per_page = isset($_GET['per_page']) ? (int) $_GET['per_page'] : $default_per_page;
if (!in_array($per_page, $allowed_per_page)) {
    $per_page = $default_per_page;
}
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

/* Build dynamic WHERE clause and params */
$where_parts = array();
$params = array();

if ($search_id !== '') {
    $where_parts[] = 'p.id = :product_id';
    $params[':product_id'] = (int) $search_id;
}

if ($search_sku !== '') {
    $where_parts[] = 'p.sku LIKE :sku';
    $params[':sku'] = '%' . $search_sku . '%';
}

if ($search_name !== '') {
    $where_parts[] = 'p.name LIKE :pname';
    $params[':pname'] = '%' . $search_name . '%';
}

if ($search_category !== '') {
    $where_parts[] = 'c.name LIKE :category_name';
    $params[':category_name'] = '%' . $search_category . '%';
}

$where_clause = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

/* Get total count for pagination */
$count_result = Database::fetchOne(
    "SELECT COUNT(p.id) as cnt FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     $where_clause",
    $params
);
$total_products = (int) ($count_result['cnt'] ?? 0);
$total_pages = ($total_products > 0) ? ceil($total_products / $per_page) : 1;

/* Clamp page to valid range */
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

/* Fetch paginated and filtered products */
$products = Database::query(
    "SELECT p.id, p.sku, p.name, p.price, p.stock,
            IFNULL(c.name,'-') AS category
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     $where_clause
     ORDER BY p.name
     LIMIT :offset, :limit",
    array_merge($params, array(':offset' => $offset, ':limit' => $per_page))
)->fetchAll();

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

/* Flash messages */
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
    <a href="product_add.php" class="btn">+ Add Product</a>
</p>

<!-- Search Form -->
<div style="background-color: #f5f5f5; padding: 12px; margin-bottom: 15px; border-radius: 4px;">
    <form method="get" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto auto; gap: 10px; align-items: flex-end;">
        <div>
            <label><strong>Product ID</strong></label>
            <input type="text" name="search_id" value="<?php echo htmlspecialchars($search_id); ?>" placeholder="e.g. 123" style="width: 100%; padding: 6px; box-sizing: border-box;">
        </div>
        <div>
            <label><strong>SKU</strong></label>
            <input type="text" name="search_sku" value="<?php echo htmlspecialchars($search_sku); ?>" placeholder="e.g. SKU-001" style="width: 100%; padding: 6px; box-sizing: border-box;">
        </div>
        <div>
            <label><strong>Name</strong></label>
            <input type="text" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>" placeholder="e.g. Apple" style="width: 100%; padding: 6px; box-sizing: border-box;">
        </div>
        <div>
            <label><strong>Category</strong></label>
            <input type="text" name="search_category" value="<?php echo htmlspecialchars($search_category); ?>" placeholder="e.g. Fruit" style="width: 100%; padding: 6px; box-sizing: border-box;">
        </div>
        <button type="submit" class="btn" style="margin: 0;">Search</button>
        <a href="products.php" class="btn" style="margin: 0; text-align: center; text-decoration: none;">Clear</a>
    </form>
</div>

<?php
/* Show search summary */
$has_search = !empty($search_id) || !empty($search_sku) || !empty($search_name) || !empty($search_category);
if ($has_search) {
    echo '<p style="color: #666;"><strong>Search Results:</strong> ' . count($products) . ' product' . (count($products) !== 1 ? 's' : '') . ' found</p>';
}
?>

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
                <a href="product_view.php?id=<?php echo $row['id']; ?>">History</a> |
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

<?php if ($total_products > 0): ?>
    <!-- Per-page selector -->
    <div style="margin-top: 15px; margin-bottom: 15px;">
        <form method="get" style="display: inline-block;">
            <?php
            // Preserve search filters
            foreach (array('search_id', 'search_sku', 'search_name', 'search_category') as $param) {
                $val = isset($_GET[$param]) ? $_GET[$param] : '';
                if ($val !== '') {
                    echo '<input type="hidden" name="' . htmlspecialchars($param) . '" value="' . htmlspecialchars($val) . '">';
                }
            }
            ?>
            <label>Show
                <select name="per_page" onchange="this.form.submit()" style="padding: 4px;">
                    <?php foreach ($allowed_per_page as $v): ?>
                        <option value="<?php echo $v; ?>"<?php if ($per_page == $v) echo ' selected'; ?>><?php echo $v; ?></option>
                    <?php endforeach; ?>
                </select>
            products per page</label>
            <input type="hidden" name="page" value="1">
        </form>
    </div>

    <?php if ($total_pages > 1): ?>
        <!-- Pagination controls -->
        <div class="pagination" style="margin-top: 12px; text-align: center;">
            <?php
            // Build base query string preserving search filters and per_page
            $base_params = array();
            foreach (array('search_id', 'search_sku', 'search_name', 'search_category', 'per_page') as $param) {
                $val = isset($_GET[$param]) ? $_GET[$param] : '';
                if ($val !== '' || $param === 'per_page') {
                    $base_params[$param] = $val;
                }
            }
            $base_params['per_page'] = $per_page;
            
            $base = '?';
            foreach ($base_params as $k => $v) {
                if ($v !== '') {
                    $base .= urlencode($k) . '=' . urlencode($v) . '&';
                }
            }
            
            // Previous link
            if ($page > 1) {
                echo '<a href="' . htmlspecialchars($base . 'page=' . ($page - 1)) . '" style="margin-right: 5px;">← Previous</a> ';
            }
            
            // Numbered links (smart pagination: show pages around current)
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1) {
                echo '<a href="' . htmlspecialchars($base . 'page=1') . '" style="margin-right: 5px;">1</a> ';
                if ($start_page > 2) {
                    echo '<span style="margin-right: 5px;">...</span> ';
                }
            }
            
            for ($p = $start_page; $p <= $end_page; $p++) {
                if ($p == $page) {
                    echo '<strong style="margin: 0 5px;">' . $p . '</strong> ';
                } else {
                    echo '<a href="' . htmlspecialchars($base . 'page=' . $p) . '" style="margin-right: 5px;">' . $p . '</a> ';
                }
            }
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span style="margin-right: 5px;">...</span> ';
                }
                echo '<a href="' . htmlspecialchars($base . 'page=' . $total_pages) . '" style="margin-right: 5px;">' . $total_pages . '</a> ';
            }
            
            // Next link
            if ($page < $total_pages) {
                echo '<a href="' . htmlspecialchars($base . 'page=' . ($page + 1)) . '" style="margin-right: 5px;">Next →</a>';
            }
            ?>
        </div>
        <p style="text-align: center; color: #666; font-size: 0.9em;">Page <?php echo $page; ?> of <?php echo $total_pages; ?> | Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products</p>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
