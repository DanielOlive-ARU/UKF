<?php
/**
 * Legacy bootstrap retained so pages including includes/db.php pick up
 * the shared PDO helper without rewriting every include statement.
 * Tests can opt out by defining USE_DATABASE_STUB to inject a fake Database.
 */
if (!defined('USE_DATABASE_STUB')) {
    require_once dirname(__DIR__) . '/../includes/database.php';
}
?>
