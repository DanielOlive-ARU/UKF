<?php
/* label_print.php – generate barcode labels for warehouse products */
include 'includes/db.php';
if (!defined('USE_DATABASE_STUB')) {
    require_once dirname(__DIR__) . '/includes/database.php';
}
require_once __DIR__ . '/includes/qr_helper.php';
include 'includes/header.php';

$notice = '';
$printJob = null;
$selectedProductId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$copiesInput = isset($_POST['copies']) ? (int)$_POST['copies'] : 1;
if ($copiesInput < 1) {
    $copiesInput = 1;
}

/* Hydrate the printer dropdown from past log entries; if it fails we fall back to manual entry. */
$printerOptions = array();
try {
  $printerStatement = Database::query(
    'SELECT DISTINCT printer_name FROM print_log ORDER BY printer_name ASC'
  );
    $printerOptions = $printerStatement->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $printerException) {
  $printerOptions = array();
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

                $qrDataUri = QrHelper::dataUri($product['sku']);
                $qrWarning = null;
                if ($qrDataUri === null) {
                    $qrWarning = extension_loaded('gd')
                        ? 'Unable to render QR code for this SKU.'
                        : 'QR code generation requires the PHP GD extension.';
                }

                /* Normalize the selected product into a single print job payload (lot, barcode, QR, dates). */
                $printJob = array(
                    'product' => $product,
                    'copies' => $copies,
                    'best_before' => $bestBefore,
                    'lot' => $lotNumber,
                    'barcode' => $barcodeText,
                    'qr_data_uri' => $qrDataUri,
                    'qr_warning' => $qrWarning,
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

if (!function_exists('pagerUrlLabel')) {
    function pagerUrlLabel($pageNumber, $filters)
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
}
?>
<div class="screen-only label-intro">
  <h2>Label Printing</h2>
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
        <th class="col-name">Name</th>
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
        <td class="col-name"><?php echo htmlspecialchars($row['name']); ?></td>
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
      <a href="<?php echo htmlspecialchars(pagerUrlLabel($page - 1, $filters)); ?>">&laquo; Prev</a>
    <?php else: ?>
        <span class="disabled">&laquo; Prev</span>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $maxPage; $i++): ?>
        <?php if ($i == $page): ?>
            <span class="current"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars(pagerUrlLabel($i, $filters)); ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($page < $maxPage): ?>
        <a href="<?php echo htmlspecialchars(pagerUrlLabel($page + 1, $filters)); ?>">Next &raquo;</a>
    <?php else: ?>
        <span class="disabled">Next &raquo;</span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <label>Copies to Print (1-99)
    <input type="number" name="copies" min="1" max="99" value="<?php echo htmlspecialchars((string)$copiesInput); ?>" required>
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
    <?php /* Printer logging form toggles between saved dropdown and manual entry, posting to the logging endpoint. */ ?>
    <form class="print-controls" id="print-log-form" method="post" action="label_print_log.php" data-has-options="<?php echo !empty($printerOptions) ? '1' : '0'; ?>">
      <?php echo Csrf::field('wh_label_log'); ?>
      <input type="hidden" name="sku" value="<?php echo htmlspecialchars($printJob['product']['sku']); ?>">
      <input type="hidden" name="copies" value="<?php echo (int)$printJob['copies']; ?>">
      <label class="printer-field">
        <span>Printer</span>
        <select name="printer_name" id="printer-name">
          <option value="">Select a printer…</option>
          <?php foreach ($printerOptions as $printerName): ?>
            <?php if ($printerName === null || $printerName === '') { continue; } ?>
            <option value="<?php echo htmlspecialchars($printerName); ?>"><?php echo htmlspecialchars($printerName); ?></option>
          <?php endforeach; ?>
          <option value="__custom" <?php echo empty($printerOptions) ? 'selected' : ''; ?>>Manual entry…</option>
        </select>
      </label>
      <label class="printer-custom<?php echo empty($printerOptions) ? ' visible' : ''; ?>" id="printer-custom-wrapper">
        <span>Manual printer name</span>
        <input type="text" name="printer_name_custom" id="printer-name-custom" placeholder="e.g. Zebra GK420d">
      </label>
      <button type="button" id="print-log-button">Log &amp; Print</button>
    </form>
  </div>
  <p id="print-log-message" class="print-log-message screen-only" role="status" aria-live="polite"></p>
  <?php if (!empty($printJob['qr_warning'])): ?>
    <p class="qr-warning screen-only">QR notice: <?php echo htmlspecialchars($printJob['qr_warning']); ?></p>
  <?php endif; ?>

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
          <div class="label-qr">
            <?php if (!empty($printJob['qr_data_uri'])): ?>
              <img
                class="qr-code"
                src="<?php echo htmlspecialchars($printJob['qr_data_uri']); ?>"
                alt="QR code for SKU <?php echo htmlspecialchars($printJob['product']['sku']); ?>">
            <?php endif; ?>
          </div>
        </div>
        <div class="label-barcode">
          <div class="barcode" aria-label="Barcode for <?php echo htmlspecialchars($printJob['product']['sku']); ?>"><?php echo htmlspecialchars($printJob['barcode']); ?></div>
        </div>
      </div>
    <?php endfor; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($printJob): ?>
<script>
/* Front-end log-before-print flow: validate printer choice, post via fetch, refresh CSRF, then trigger window.print(). */
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('print-log-form');
  if (!form) {
    return;
  }

  var button = document.getElementById('print-log-button');
  var printerSelect = document.getElementById('printer-name');
  var customWrapper = document.getElementById('printer-custom-wrapper');
  var customInput = document.getElementById('printer-name-custom');
  var statusEl = document.getElementById('print-log-message');
  var hasSavedOptions = form.getAttribute('data-has-options') === '1';

  function needsCustom() {
    if (!hasSavedOptions || !printerSelect) {
      return true;
    }
    return printerSelect.value === '__custom';
  }

  function syncCustomVisibility() {
    if (!customWrapper) {
      return;
    }
    var show = needsCustom();
    if (show) {
      customWrapper.classList.add('visible');
      if (customInput) {
        customInput.focus();
      }
    } else {
      customWrapper.classList.remove('visible');
    }
  }

  if (printerSelect) {
    printerSelect.addEventListener('change', syncCustomVisibility);
    syncCustomVisibility();
  }

  if (!button) {
    return;
  }

  button.addEventListener('click', function () {
    if (needsCustom()) {
      if (customInput && customInput.value.trim() === '') {
        if (statusEl) {
          statusEl.textContent = 'Enter a printer name before printing.';
        }
        if (customInput) {
          customInput.focus();
        }
        return;
      }
    } else if (printerSelect && printerSelect.value === '') {
      if (statusEl) {
        statusEl.textContent = 'Select a printer before printing.';
      }
      printerSelect.focus();
      return;
    }

    if (statusEl) {
      statusEl.textContent = 'Logging print job...';
    }
    button.disabled = true;

    var formData = new FormData(form);
    fetch('label_print_log.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Server responded with ' + response.status);
        }
        return response.json();
      })
      .then(function (payload) {
        if (payload && payload.status === 'ok') {
          if (payload.nextToken) {
            var tokenInput = form.querySelector('input[name="csrf_token"]');
            if (tokenInput) {
              tokenInput.value = payload.nextToken;
            }
          }
          if (statusEl && payload.loggedAt) {
            statusEl.textContent = 'Print logged at ' + payload.loggedAt + '.';
          }
        } else {
          throw new Error(payload && payload.message ? payload.message : 'Unknown error');
        }
      })
      .catch(function (error) {
        if (statusEl) {
          statusEl.textContent = 'Could not log print job: ' + error.message + '. Printing anyway.';
        }
      })
      .finally(function () {
        button.disabled = false;
        window.print();
      });
  });
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
