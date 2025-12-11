<?php
/* reports.php – Warehouse reports with pagination and filtering */
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* ---------- Configuration ---------- */
$perPage = 15;

/* ---------- Determine active report ---------- */
$validReports = array(
    'last_printed',
    'printed_today',
    'never_printed',
    'not_printed_days',
    'missing_data',
    'deliveries_today',
    'adjustments_30d',
    'qa_failures_30d'
);
$activeReport = isset($_GET['report']) ? $_GET['report'] : 'last_printed';
if (!in_array($activeReport, $validReports)) {
    $activeReport = 'last_printed';
}

/* ---------- Filters ---------- */
$filters = array(
    'category_id' => isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0,
    'days' => isset($_GET['days']) ? (int)$_GET['days'] : 30
);
if ($filters['days'] < 1) {
    $filters['days'] = 30;
}

/* Load categories for filter dropdown */
$categories = Database::query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

/* ---------- Pagination helper ---------- */
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

function pagerUrl($pageNumber, $activeReport, $filters)
{
    $query = array('report' => $activeReport, 'page' => $pageNumber);
    if ($filters['category_id'] > 0) {
        $query['category_id'] = $filters['category_id'];
    }
    if ($filters['days'] != 30) {
        $query['days'] = $filters['days'];
    }
    return 'reports.php?' . http_build_query($query);
}

function reportUrl($report, $filters = array())
{
    $query = array('report' => $report);
    if (isset($filters['category_id']) && $filters['category_id'] > 0) {
        $query['category_id'] = $filters['category_id'];
    }
    if (isset($filters['days']) && $filters['days'] != 30) {
        $query['days'] = $filters['days'];
    }
    return 'reports.php?' . http_build_query($query);
}

/* ---------- Build category filter SQL fragment ---------- */
function categoryWhere($alias, $categoryId, &$params)
{
    if ($categoryId > 0) {
        $params[':category_id'] = $categoryId;
        return " AND {$alias}.category_id = :category_id";
    }
    return '';
}

/* ---------- Report data ---------- */
$rows = array();
$totalRows = 0;
$maxPage = 1;
$reportTitle = '';
$columns = array();
$lastPrinted = null;

/* Always fetch "Last Item Printed" for the summary card */
$lastPrintedRow = Database::fetchOne(
    "SELECT pl.sku, pl.copies, pl.printed_at, pl.printer_name, p.name AS product_name
     FROM print_log pl
     LEFT JOIN products p ON p.sku = pl.sku
     ORDER BY pl.printed_at DESC
     LIMIT 1"
);
if ($lastPrintedRow) {
    $lastPrinted = $lastPrintedRow;
}

switch ($activeReport) {

    case 'printed_today':
        $reportTitle = 'Products Printed Today';
        $columns = array('SKU', 'Name', 'Total Copies', 'Last Printed');

        $params = array();
        $catWhere = categoryWhere('p', $filters['category_id'], $params);

        $countSql = "SELECT COUNT(DISTINCT pl.sku) AS total
                     FROM print_log pl
                     LEFT JOIN products p ON p.sku = pl.sku
                     WHERE DATE(pl.printed_at) = CURDATE() $catWhere";
        $countRow = Database::fetchOne($countSql, $params);
        $totalRows = $countRow ? (int)$countRow['total'] : 0;
        $maxPage = max(1, (int)ceil($totalRows / $perPage));
        if ($page > $maxPage) $page = $maxPage;
        $offset = ($page - 1) * $perPage;

        $rows = Database::query(
            "SELECT pl.sku, p.name, SUM(pl.copies) AS total_copies, MAX(pl.printed_at) AS last_printed
             FROM print_log pl
             LEFT JOIN products p ON p.sku = pl.sku
             WHERE DATE(pl.printed_at) = CURDATE() $catWhere
             GROUP BY pl.sku, p.name
             ORDER BY last_printed DESC
             LIMIT $perPage OFFSET $offset",
            $params
        )->fetchAll();
        break;

    case 'never_printed':
        $reportTitle = 'Products Never Printed';
        $columns = array('SKU', 'Name', 'Category', 'Stock');

        $params = array();
        $catWhere = categoryWhere('p', $filters['category_id'], $params);

        $countSql = "SELECT COUNT(*) AS total
                     FROM products p
                     LEFT JOIN print_log pl ON pl.sku = p.sku
                     WHERE pl.sku IS NULL $catWhere";
        $countRow = Database::fetchOne($countSql, $params);
        $totalRows = $countRow ? (int)$countRow['total'] : 0;
        $maxPage = max(1, (int)ceil($totalRows / $perPage));
        if ($page > $maxPage) $page = $maxPage;
        $offset = ($page - 1) * $perPage;

        $rows = Database::query(
            "SELECT p.sku, p.name, c.name AS category_name, p.stock
             FROM products p
             LEFT JOIN print_log pl ON pl.sku = p.sku
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE pl.sku IS NULL $catWhere
             ORDER BY p.sku ASC
             LIMIT $perPage OFFSET $offset",
            $params
        )->fetchAll();
        break;

    case 'not_printed_days':
        $reportTitle = 'Products Not Printed in ' . $filters['days'] . ' Days';
        $columns = array('SKU', 'Name', 'Last Printed', 'Days Ago');

        $params = array(':days' => $filters['days']);
        $catWhere = categoryWhere('p', $filters['category_id'], $params);

        /* Products with no print in X days OR never printed */
        $countSql = "SELECT COUNT(*) AS total
                     FROM products p
                     LEFT JOIN (
                         SELECT sku, MAX(printed_at) AS last_printed
                         FROM print_log
                         GROUP BY sku
                     ) lp ON lp.sku = p.sku
                     WHERE (lp.last_printed IS NULL OR lp.last_printed < DATE_SUB(CURDATE(), INTERVAL :days DAY)) $catWhere";
        $countRow = Database::fetchOne($countSql, $params);
        $totalRows = $countRow ? (int)$countRow['total'] : 0;
        $maxPage = max(1, (int)ceil($totalRows / $perPage));
        if ($page > $maxPage) $page = $maxPage;
        $offset = ($page - 1) * $perPage;

        $rows = Database::query(
            "SELECT p.sku, p.name, lp.last_printed,
                    DATEDIFF(CURDATE(), lp.last_printed) AS days_ago
             FROM products p
             LEFT JOIN (
                 SELECT sku, MAX(printed_at) AS last_printed
                 FROM print_log
                 GROUP BY sku
             ) lp ON lp.sku = p.sku
             WHERE (lp.last_printed IS NULL OR lp.last_printed < DATE_SUB(CURDATE(), INTERVAL :days DAY)) $catWhere
             ORDER BY lp.last_printed ASC, p.sku ASC
             LIMIT $perPage OFFSET $offset",
            $params
        )->fetchAll();
        break;

    case 'missing_data':
        $reportTitle = 'Products Missing Data';
        $columns = array('SKU', 'Name', 'Missing Fields');

        $params = array();
        $catWhere = categoryWhere('p', $filters['category_id'], $params);

        /* Missing: name, country_iso, class, price=0, best_before_days=0, or (PF-* and no weight) */
        $missingCondition = "(
            p.name IS NULL OR p.name = '' OR
            p.country_iso IS NULL OR p.country_iso = '' OR
            p.class IS NULL OR p.class = '' OR
            p.price IS NULL OR p.price = 0 OR
            p.best_before_days IS NULL OR p.best_before_days = 0 OR
            (p.sku LIKE 'PF%' AND (p.default_pack_weight_g IS NULL OR p.default_pack_weight_g = 0))
        )";

        $countSql = "SELECT COUNT(*) AS total FROM products p WHERE $missingCondition $catWhere";
        $countRow = Database::fetchOne($countSql, $params);
        $totalRows = $countRow ? (int)$countRow['total'] : 0;
        $maxPage = max(1, (int)ceil($totalRows / $perPage));
        if ($page > $maxPage) $page = $maxPage;
        $offset = ($page - 1) * $perPage;

        $rows = Database::query(
            "SELECT p.sku, p.name, p.country_iso, p.class, p.price, p.best_before_days, p.default_pack_weight_g
             FROM products p
             WHERE $missingCondition $catWhere
             ORDER BY p.sku ASC
             LIMIT $perPage OFFSET $offset",
            $params
        )->fetchAll();

        /* Build "missing fields" string for each row */
        foreach ($rows as &$row) {
            $missing = array();
            if (empty($row['name'])) $missing[] = 'Name';
            if (empty($row['country_iso'])) $missing[] = 'Country';
            if (empty($row['class'])) $missing[] = 'Class';
            if (empty($row['price']) || $row['price'] == 0) $missing[] = 'Price';
            if (empty($row['best_before_days']) || $row['best_before_days'] == 0) $missing[] = 'Best Before';
            if (strpos($row['sku'], 'PF') === 0 && (empty($row['default_pack_weight_g']) || $row['default_pack_weight_g'] == 0)) {
                $missing[] = 'Weight';
            }
            $row['missing_fields'] = implode(', ', $missing);
        }
        unset($row);
        break;

    case 'deliveries_today':
        $reportTitle = 'Deliveries Today (' . date('Y-m-d') . ')';
        $columns = array('ID', 'Time', 'SKU', 'Name', 'Qty', 'Supplier Ref');

        $params = array();
        $catWhere = categoryWhere('p', $filters['category_id'], $params);

        $countSql = "SELECT COUNT(*) AS total
                     FROM deliveries d
                     JOIN products p ON p.id = d.product_id
                     WHERE DATE(d.received_at) = CURDATE() $catWhere";
        $countRow = Database::fetchOne($countSql, $params);
        $totalRows = $countRow ? (int)$countRow['total'] : 0;
        $maxPage = max(1, (int)ceil($totalRows / $perPage));
        if ($page > $maxPage) $page = $maxPage;
        $offset = ($page - 1) * $perPage;

        $rows = Database::query(
            "SELECT d.id, d.received_at, p.sku, p.name, d.qty, d.supplier_ref
             FROM deliveries d
             JOIN products p ON p.id = d.product_id
             WHERE DATE(d.received_at) = CURDATE() $catWhere
             ORDER BY d.received_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        )->fetchAll();
        break;

    case 'adjustments_30d':
        $reportTitle = 'Adjustments (Last 30 Days)';
        $columns = array('ID', 'Date', 'SKU', 'Name', 'Δ Qty', 'Reason');

        $params = array();
        $catWhere = categoryWhere('p', $filters['category_id'], $params);

        $countSql = "SELECT COUNT(*) AS total
                     FROM adjustments a
                     JOIN products p ON p.id = a.product_id
                     WHERE a.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) $catWhere";
        $countRow = Database::fetchOne($countSql, $params);
        $totalRows = $countRow ? (int)$countRow['total'] : 0;
        $maxPage = max(1, (int)ceil($totalRows / $perPage));
        if ($page > $maxPage) $page = $maxPage;
        $offset = ($page - 1) * $perPage;

        $rows = Database::query(
            "SELECT a.id, a.created_at, p.sku, p.name, a.qty_delta, a.reason
             FROM adjustments a
             JOIN products p ON p.id = a.product_id
             WHERE a.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) $catWhere
             ORDER BY a.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        )->fetchAll();
        break;

    case 'qa_failures_30d':
        $reportTitle = 'QA Failures (Last 30 Days)';
        $columns = array('ID', 'Date', 'SKU', 'Name', 'Brix', 'Temp °C');

        $params = array();
        $catWhere = categoryWhere('p', $filters['category_id'], $params);

        $countSql = "SELECT COUNT(*) AS total
                     FROM qa_samples q
                     JOIN products p ON p.id = q.product_id
                     WHERE q.passed = 'no' AND q.sample_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) $catWhere";
        $countRow = Database::fetchOne($countSql, $params);
        $totalRows = $countRow ? (int)$countRow['total'] : 0;
        $maxPage = max(1, (int)ceil($totalRows / $perPage));
        if ($page > $maxPage) $page = $maxPage;
        $offset = ($page - 1) * $perPage;

        $rows = Database::query(
            "SELECT q.id, q.sample_time, p.sku, p.name, q.brix, q.temperature
             FROM qa_samples q
             JOIN products p ON p.id = q.product_id
             WHERE q.passed = 'no' AND q.sample_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) $catWhere
             ORDER BY q.sample_time DESC
             LIMIT $perPage OFFSET $offset",
            $params
        )->fetchAll();
        break;

    case 'last_printed':
    default:
        $activeReport = 'last_printed';
        $reportTitle = 'Last Item Printed';
        $columns = array();
        /* This is just the summary card, no table needed */
        break;
}
?>
<h2>Warehouse Reports</h2>

<!-- Last Printed Summary Card -->
<section class="filter-card" style="margin-bottom:1.5rem;">
  <h3 style="margin-top:0;">🏷️ Last Item Printed</h3>
  <?php if ($lastPrinted): ?>
    <div class="cards" style="margin-bottom:0;">
      <div class="card">
        <span class="big"><?php echo htmlspecialchars($lastPrinted['sku']); ?></span>
        <?php echo htmlspecialchars($lastPrinted['product_name'] ?: '(Unknown product)'); ?>
      </div>
      <div class="card">
        <span class="big"><?php echo (int)$lastPrinted['copies']; ?></span>
        Copies
      </div>
      <div class="card">
        <span class="big"><?php echo date('H:i', strtotime($lastPrinted['printed_at'])); ?></span>
        <?php echo date('Y-m-d', strtotime($lastPrinted['printed_at'])); ?>
      </div>
      <div class="card">
        <span class="big" style="font-size:1rem;"><?php echo htmlspecialchars($lastPrinted['printer_name']); ?></span>
        Printer
      </div>
    </div>
  <?php else: ?>
    <p>No labels have been printed yet.</p>
  <?php endif; ?>
</section>

<!-- Report Navigation -->
<section class="filter-card">
  <h3 style="margin-top:0;">Select Report</h3>
  <div class="filter-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
    <a href="<?php echo reportUrl('printed_today', $filters); ?>" class="btn <?php echo $activeReport === 'printed_today' ? '' : 'ghost'; ?>">Printed Today</a>
    <a href="<?php echo reportUrl('never_printed', $filters); ?>" class="btn <?php echo $activeReport === 'never_printed' ? '' : 'ghost'; ?>">Never Printed</a>
    <a href="<?php echo reportUrl('not_printed_days', $filters); ?>" class="btn <?php echo $activeReport === 'not_printed_days' ? '' : 'ghost'; ?>">Not Printed (Days)</a>
    <a href="<?php echo reportUrl('missing_data', $filters); ?>" class="btn <?php echo $activeReport === 'missing_data' ? '' : 'ghost'; ?>">Missing Data</a>
    <a href="<?php echo reportUrl('deliveries_today', $filters); ?>" class="btn <?php echo $activeReport === 'deliveries_today' ? '' : 'ghost'; ?>">Deliveries Today</a>
    <a href="<?php echo reportUrl('adjustments_30d', $filters); ?>" class="btn <?php echo $activeReport === 'adjustments_30d' ? '' : 'ghost'; ?>">Adjustments (30d)</a>
    <a href="<?php echo reportUrl('qa_failures_30d', $filters); ?>" class="btn <?php echo $activeReport === 'qa_failures_30d' ? '' : 'ghost'; ?>">QA Failures (30d)</a>
  </div>
</section>

<!-- Filters -->
<?php if ($activeReport !== 'last_printed'): ?>
<section class="filter-card">
  <form method="get" class="filter-form">
    <input type="hidden" name="report" value="<?php echo htmlspecialchars($activeReport); ?>">
    <div class="filter-grid">
      <label>Category
        <select name="category_id">
          <option value="0">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php echo $filters['category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if ($activeReport === 'not_printed_days'): ?>
      <label>Days Threshold
        <input type="number" name="days" min="1" max="365" value="<?php echo $filters['days']; ?>">
      </label>
      <?php endif; ?>
    </div>
    <p>
      <button type="submit">Apply Filters</button>
      <a href="<?php echo reportUrl($activeReport); ?>" class="btn ghost">Reset</a>
    </p>
  </form>
</section>
<?php endif; ?>

<!-- Report Results -->
<?php if ($activeReport !== 'last_printed'): ?>
<section>
  <h3><?php echo htmlspecialchars($reportTitle); ?></h3>
  <p style="color:#666;">Showing <?php echo count($rows); ?> of <?php echo $totalRows; ?> results</p>

  <table>
    <thead>
      <tr>
        <?php foreach ($columns as $col): ?>
          <th><?php echo htmlspecialchars($col); ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="<?php echo count($columns); ?>">No records found.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <?php switch ($activeReport):
            case 'printed_today': ?>
              <td><?php echo htmlspecialchars($r['sku']); ?></td>
              <td><?php echo htmlspecialchars($r['name'] ?: '(Unknown)'); ?></td>
              <td><?php echo (int)$r['total_copies']; ?></td>
              <td><?php echo date('H:i', strtotime($r['last_printed'])); ?></td>
              <?php break;

            case 'never_printed': ?>
              <td><?php echo htmlspecialchars($r['sku']); ?></td>
              <td><?php echo htmlspecialchars($r['name']); ?></td>
              <td><?php echo htmlspecialchars($r['category_name'] ?: '-'); ?></td>
              <td><?php echo (int)$r['stock']; ?></td>
              <?php break;

            case 'not_printed_days': ?>
              <td><?php echo htmlspecialchars($r['sku']); ?></td>
              <td><?php echo htmlspecialchars($r['name']); ?></td>
              <td><?php echo $r['last_printed'] ? date('Y-m-d', strtotime($r['last_printed'])) : 'Never'; ?></td>
              <td><?php echo $r['days_ago'] !== null ? (int)$r['days_ago'] : '-'; ?></td>
              <?php break;

            case 'missing_data': ?>
              <td><?php echo htmlspecialchars($r['sku']); ?></td>
              <td><?php echo htmlspecialchars($r['name'] ?: '(empty)'); ?></td>
              <td style="color:#a00;"><?php echo htmlspecialchars($r['missing_fields']); ?></td>
              <?php break;

            case 'deliveries_today': ?>
              <td><?php echo $r['id']; ?></td>
              <td><?php echo date('H:i', strtotime($r['received_at'])); ?></td>
              <td><?php echo htmlspecialchars($r['sku']); ?></td>
              <td><?php echo htmlspecialchars($r['name']); ?></td>
              <td><?php echo (int)$r['qty']; ?></td>
              <td><?php echo htmlspecialchars($r['supplier_ref']); ?></td>
              <?php break;

            case 'adjustments_30d': ?>
              <td><?php echo $r['id']; ?></td>
              <td><?php echo date('Y-m-d', strtotime($r['created_at'])); ?></td>
              <td><?php echo htmlspecialchars($r['sku']); ?></td>
              <td><?php echo htmlspecialchars($r['name']); ?></td>
              <td><?php echo (int)$r['qty_delta']; ?></td>
              <td><?php echo htmlspecialchars($r['reason']); ?></td>
              <?php break;

            case 'qa_failures_30d': ?>
              <td><?php echo $r['id']; ?></td>
              <td><?php echo date('Y-m-d', strtotime($r['sample_time'])); ?></td>
              <td><?php echo htmlspecialchars($r['sku']); ?></td>
              <td><?php echo htmlspecialchars($r['name']); ?></td>
              <td><?php echo $r['brix']; ?></td>
              <td><?php echo $r['temperature']; ?></td>
              <?php break;

          endswitch; ?>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <?php if ($maxPage > 1): ?>
  <div class="pager">
    <?php if ($page > 1): ?>
      <a href="<?php echo htmlspecialchars(pagerUrl($page - 1, $activeReport, $filters)); ?>">&laquo; Prev</a>
    <?php else: ?>
      <span class="disabled">&laquo; Prev</span>
    <?php endif; ?>

    <?php
    /* Show limited page numbers for large result sets */
    $startPage = max(1, $page - 2);
    $endPage = min($maxPage, $page + 2);
    if ($startPage > 1) echo '<span class="disabled">...</span>';
    for ($i = $startPage; $i <= $endPage; $i++): ?>
      <?php if ($i == $page): ?>
        <span class="current"><?php echo $i; ?></span>
      <?php else: ?>
        <a href="<?php echo htmlspecialchars(pagerUrl($i, $activeReport, $filters)); ?>"><?php echo $i; ?></a>
      <?php endif; ?>
    <?php endfor;
    if ($endPage < $maxPage) echo '<span class="disabled">...</span>';
    ?>

    <?php if ($page < $maxPage): ?>
      <a href="<?php echo htmlspecialchars(pagerUrl($page + 1, $activeReport, $filters)); ?>">Next &raquo;</a>
    <?php else: ?>
      <span class="disabled">Next &raquo;</span>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<p style="margin-top:2rem;"><a href="dashboard.php">← Back to Dashboard</a></p>

<?php include 'includes/footer.php'; ?>
