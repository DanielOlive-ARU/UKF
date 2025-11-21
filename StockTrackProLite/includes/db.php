<?php
/**
 * Legacy bootstrap kept so existing pages can include includes/db.php.
 * Connection management now happens through the shared PDO helper.
 */
require_once dirname(__DIR__) . '/../includes/database.php';
?>
