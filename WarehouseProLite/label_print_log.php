<?php
declare(strict_types=1);

include 'includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/security.php';

header('Content-Type: application/json');

/**
 * Emit a JSON response and terminate.
 */
function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, array('status' => 'error', 'message' => 'Method not allowed.'));
}

$token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!Csrf::validate($token, 'wh_label_log')) {
    respond(400, array('status' => 'error', 'message' => 'Session expired. Refresh the page and try again.'));
}

$sku = isset($_POST['sku']) ? trim($_POST['sku']) : '';
$copies = isset($_POST['copies']) ? (int)$_POST['copies'] : 0;
$printerSelection = isset($_POST['printer_name']) ? trim($_POST['printer_name']) : '';
$printerCustom = isset($_POST['printer_name_custom']) ? trim($_POST['printer_name_custom']) : '';

$printerName = $printerSelection === '__custom' ? $printerCustom : $printerSelection;
$printerName = trim($printerName);

$sku = preg_replace('/[^ -~]/', '', $sku);
$printerName = preg_replace('/[\r\n]+/', ' ', $printerName);

if ($sku === '' || $copies < 1 || $copies > 99 || $printerName === '') {
    respond(400, array('status' => 'error', 'message' => 'Missing SKU, copies, or printer name.'));
}

if (strlen($sku) > 30) {
    $sku = substr($sku, 0, 30);
}
if (strlen($printerName) > 100) {
    $printerName = substr($printerName, 0, 100);
}

$packDate = date('Y-m-d 00:00:00');
$printedAt = date('Y-m-d H:i:s');

try {
    Database::query(
        'INSERT INTO print_log (sku, copies, pack_date, printed_at, printer_name)
         VALUES (:sku, :copies, :pack_date, :printed_at, :printer_name)',
        array(
            ':sku' => $sku,
            ':copies' => $copies,
            ':pack_date' => $packDate,
            ':printed_at' => $printedAt,
            ':printer_name' => $printerName
        )
    );
} catch (Exception $exception) {
    respond(500, array('status' => 'error', 'message' => 'Unable to save print log entry.'));
}

$nextToken = Csrf::token('wh_label_log');

respond(200, array(
    'status' => 'ok',
    'loggedAt' => $printedAt,
    'nextToken' => $nextToken
));
