<?php
/**
 * PHP QR Code encoder
 *
 * Based on the original PHP QR Code library by Dominik Dzienia (http://phpqrcode.sourceforge.net/)
 * Packaged locally so the Warehouse app can generate QR images without remote calls.
 */

if (defined('QR_CACHEABLE') === false) {
    define('QR_CACHEABLE', false);
}
if (defined('QR_CACHE_DIR') === false) {
    define('QR_CACHE_DIR', false);
}
if (defined('QR_LOG_DIR') === false) {
    define('QR_LOG_DIR', false);
}
if (defined('QR_FIND_BEST_MASK') === false) {
    define('QR_FIND_BEST_MASK', true);
}
if (defined('QR_FIND_FROM_RANDOM') === false) {
    define('QR_FIND_FROM_RANDOM', 2);
}
if (defined('QR_DEFAULT_MASK') === false) {
    define('QR_DEFAULT_MASK', 2);
}
if (defined('QR_PNG_MAXIMUM_SIZE') === false) {
    define('QR_PNG_MAXIMUM_SIZE', 1024);
}

// Error correction levels
if (!defined('QR_ECLEVEL_L')) define('QR_ECLEVEL_L', 0);
if (!defined('QR_ECLEVEL_M')) define('QR_ECLEVEL_M', 1);
if (!defined('QR_ECLEVEL_Q')) define('QR_ECLEVEL_Q', 2);
if (!defined('QR_ECLEVEL_H')) define('QR_ECLEVEL_H', 3);

// Modes
if (!defined('QR_MODE_NUMBER')) define('QR_MODE_NUMBER', 1);
if (!defined('QR_MODE_ALPHANUM')) define('QR_MODE_ALPHANUM', 2);
if (!defined('QR_MODE_8BIT')) define('QR_MODE_8BIT', 4);
if (!defined('QR_MODE_KANJI')) define('QR_MODE_KANJI', 8);

if (!defined('QR_MASK_PATTERN_AUTO')) define('QR_MASK_PATTERN_AUTO', -1);

require_once __DIR__ . '/phpqrcode/qrconst.php';
require_once __DIR__ . '/phpqrcode/qrtools.php';
require_once __DIR__ . '/phpqrcode/qrspec.php';
require_once __DIR__ . '/phpqrcode/qrimage.php';
require_once __DIR__ . '/phpqrcode/qrinput.php';
require_once __DIR__ . '/phpqrcode/qrbitstream.php';
require_once __DIR__ . '/phpqrcode/qrcharacter.php';
require_once __DIR__ . '/phpqrcode/qrrscode.php';
require_once __DIR__ . '/phpqrcode/qrrawcode.php';
require_once __DIR__ . '/phpqrcode/qrframe.php';
require_once __DIR__ . '/phpqrcode/qrsplit.php';
require_once __DIR__ . '/phpqrcode/qrdata.php';
require_once __DIR__ . '/phpqrcode/qrvect.php';
require_once __DIR__ . '/phpqrcode/qrmask.php';
require_once __DIR__ . '/phpqrcode/qroutput.php';
require_once __DIR__ . '/phpqrcode/qrcode.php';
