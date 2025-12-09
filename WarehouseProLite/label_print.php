<?php
/* label_print.php – generate barcode labels for warehouse products */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

$notice = '';
$printJob = null;
$selectedProductId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$copiesInput = isset($_POST['copies']) ? (int)$_POST['copies'] : 1;
if ($copiesInput < 1) {
    $copiesInput = 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '', 'wh_label_print')) {
        $notice = 'Session expired. Please resubmit the form.';
    } else {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $copies = isset($_POST['copies']) ? (int)$_POST['copies'] : 0;

        if ($productId <= 0) {
            $notice = 'Select a product before generating labels.';
        } elseif ($copies < 1 || $copies > 99) {
            $notice = 'Copies must be between 1 and 99.';
        } else {
            $product = Database::fetchOne(
                "SELECT id, sku, name, country_iso, class, pack_uom, default_pack_weight_g, best_before_days
                 FROM products
                 WHERE id = :id",
                array(':id' => $productId)
            );

            if (!$product) {
                $notice = 'Product not found.';
            } else {
                $days = isset($product['best_before_days']) ? (int)$product['best_before_days'] : 0;
                if ($days < 0) {
                    $days = 0;
                }
                $bestBefore = date('d/m/Y', strtotime('+' . $days . ' days'));

                $segments = explode('-', $product['sku']);
                $lotCore = (count($segments) >= 2) ? $segments[1] : $product['sku'];
                $lotCore = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $lotCore));
                if ($lotCore === '') {
                    $lotCore = str_replace('-', '', strtoupper($product['sku']));
                }
                $lotNumber = $lotCore . date('dmY');

                $asciiSku = preg_replace('/[^ -~]/', '', $product['sku']);
                if ($asciiSku === '') {
                    $asciiSku = $product['sku'];
                }
                $barcodeText = '*' . $asciiSku . '*';

                $printJob = array(
                    'product' => $product,
                    'copies' => $copies,
                    'best_before' => $bestBefore,
                    'lot' => $lotNumber,
                    'barcode' => $barcodeText,
                    'generated_at' => date('Y-m-d H:i')
                );
            }
        }
    }
}

$filters = array(
    'sku' => isset($_GET['sku']) ? trim($_GET['sku']) : '',
    'name' => isset($_GET['name']) ? trim($_GET['name']) : '',
    'country_iso' => isset($_GET['country_iso']) ? trim($_GET['country_iso']) : ''
);

$where = array();
$params = array();
if ($filters['sku'] !== '') {
    $where[] = 'p.sku LIKE :filter_sku';
    $params[':filter_sku'] = '%' . $filters['sku'] . '%';
}
if ($filters['name'] !== '') {
    $where[] = 'p.name LIKE :filter_name';
    $params[':filter_name'] = '%' . $filters['name'] . '%';
}
if ($filters['country_iso'] !== '') {
    $where[] = 'p.country_iso LIKE :filter_country';
    $params[':filter_country'] = '%' . $filters['country_iso'] . '%';
}
$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$totalRow = Database::fetchOne("SELECT COUNT(*) AS total FROM products p $sqlWhere", $params);
$totalRows = $totalRow ? (int)$totalRow['total'] : 0;
$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$maxPage = max(1, (int)ceil($totalRows / $perPage));
if ($page > $maxPage) {
    $page = $maxPage;
}
$offset = ($page - 1) * $perPage;

$productRows = Database::query(
    "SELECT p.id, p.sku, p.name, p.country_iso, p.class, p.pack_uom, p.default_pack_weight_g, p.best_before_days
     FROM products p
     $sqlWhere
     ORDER BY p.sku ASC
     LIMIT $perPage OFFSET $offset",
    $params
)->fetchAll();

function pagerUrl($pageNumber, $filters)
{
    $query = array();
    foreach ($filters as $key => $value) {
        if ($value !== '') {
            $query[$key] = $value;
        }
    }
    $query['page'] = $pageNumber;
    return 'label_print.php?' . http_build_query($query);
}
?>
<div class="screen-only label-intro">
  <h2>Label Printing</h2>
  <p>Search for a SKU, choose up to 99 copies, and generate printable Code39 labels using the built-in Libre Barcode 39 font.</p>
</div>

<?php if ($notice): ?>
    <p class="notice"><?php echo htmlspecialchars($notice); ?></p>
<?php endif; ?>

<section class="filter-card">
  <form method="get" class="filter-form">
    <div class="filter-grid">
      <label>SKU
        <input type="text" name="sku" value="<?php echo htmlspecialchars($filters['sku']); ?>" placeholder="e.g. LF-APL-001">
      </label>
      <label>Name
        <input type="text" name="name" value="<?php echo htmlspecialchars($filters['name']); ?>" placeholder="Product name">
      </label>
      <label>Country ISO
        <input type="text" name="country_iso" value="<?php echo htmlspecialchars($filters['country_iso']); ?>" placeholder="e.g. GB">
      </label>
    </div>
    <p>
      <button type="submit">Apply Filters</button>
      <a href="label_print.php" class="btn ghost">Reset</a>
    </p>
  </form>
</section>

<form method="post" class="label-form">
  <?php echo Csrf::field('wh_label_print'); ?>
  <table>
    <thead>
      <tr>
        <th></th>
        <th>SKU</th>
        <th>Name</th>
        <th>Country</th>
        <th>Class</th>
        <th>Pack UOM</th>
        <th>Default Weight (g)</th>
        <th>Best Before (days)</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$productRows): ?>
      <tr><td colspan="8">No products found.</td></tr>
    <?php else: foreach ($productRows as $row): ?>
      <tr>
        <td><input type="radio" name="product_id" value="<?php echo $row['id']; ?>" <?php if ($selectedProductId === (int)$row['id']) echo 'checked'; ?>></td>
        <td><?php echo htmlspecialchars($row['sku']); ?></td>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo htmlspecialchars($row['country_iso']); ?></td>
        <td><?php echo htmlspecialchars($row['class']); ?></td>
        <td><?php echo htmlspecialchars($row['pack_uom']); ?></td>
        <td><?php echo (int)$row['default_pack_weight_g']; ?></td>
        <td><?php echo (int)$row['best_before_days']; ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>

  <?php if ($maxPage > 1): ?>
  <div class="pager">
    <?php if ($page > 1): ?>
        <a href="<?php echo htmlspecialchars(pagerUrl($page - 1, $filters)); ?>">&laquo; Prev</a>
    <?php else: ?>
        <span class="disabled">&laquo; Prev</span>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $maxPage; $i++): ?>
        <?php if ($i == $page): ?>
            <span class="current"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars(pagerUrl($i, $filters)); ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($page < $maxPage): ?>
        <a href="<?php echo htmlspecialchars(pagerUrl($page + 1, $filters)); ?>">Next &raquo;</a>
    <?php else: ?>
        <span class="disabled">Next &raquo;</span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <label>Copies to Print (1-99)
    <input type="number" name="copies" min="1" max="99" value="<?php echo htmlspecialchars($copiesInput); ?>" required>
  </label>

  <p>
    <input type="submit" value="Generate Preview">
  </p>
</form>

<?php if ($printJob): ?>
<section class="label-preview" id="label-preview">
  <div class="print-actions screen-only">
    <div>
      <strong>Preview:</strong> <?php echo $printJob['copies']; ?> label(s) for SKU <?php echo htmlspecialchars($printJob['product']['sku']); ?>
      &middot; Lot <?php echo htmlspecialchars($printJob['lot']); ?>
      &middot; Generated <?php echo htmlspecialchars($printJob['generated_at']); ?>
    </div>
    <button type="button" onclick="window.print()">Print Labels</button>
  </div>

  <div class="label-grid">
    <?php for ($i = 0; $i < $printJob['copies']; $i++): ?>
      <div class="label-card">
        <div class="label-header">
          <div class="label-title"><?php echo htmlspecialchars($printJob['product']['name']); ?></div>
        </div>
        <div class="label-body">
          <div class="label-info">
            <div class="label-meta">
              <?php
                $packUomValue = (string)($printJob['product']['pack_uom'] ?? '');
                $packWeightValue = $printJob['product']['default_pack_weight_g'];
                $showPackUom = $packUomValue !== '' && strcasecmp($packUomValue, 'g') !== 0;
                $showPackWeight = $packWeightValue !== null && $packWeightValue !== '';
              ?>
              <span><span class="label-term">Country</span><strong><?php echo htmlspecialchars($printJob['product']['country_iso']); ?></strong></span>
              <span><span class="label-term">Class</span><strong><?php echo htmlspecialchars($printJob['product']['class']); ?></strong></span>
              <span><span class="label-term">Pack</span><strong>
                <?php if ($showPackUom): ?><?php echo htmlspecialchars($packUomValue); ?><?php endif; ?>
                <?php if ($showPackUom && $showPackWeight): ?> @ <?php endif; ?>
                <?php if ($showPackWeight): ?><?php echo htmlspecialchars((int)$packWeightValue . 'g'); ?><?php endif; ?>
              </strong></span>
            </div>
            <div class="label-details">
              <span><span class="label-term">Best before</span><strong><?php echo htmlspecialchars($printJob['best_before']); ?></strong></span>
              <span><span class="label-term">Lot</span><strong><?php echo htmlspecialchars($printJob['lot']); ?></strong></span>
              <span><span class="label-term">SKU</span><strong><?php echo htmlspecialchars($printJob['product']['sku']); ?></strong></span>
            </div>
          </div>
        </div>
        <div>
          <div class="barcode" aria-label="Barcode for <?php echo htmlspecialchars($printJob['product']['sku']); ?>"><?php echo htmlspecialchars($printJob['barcode']); ?></div>
        </div>
      </div>
    <?php endfor; ?>
  </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
