<?php
/**
 * Legacy bootstrap retained so pages including includes/db.php pick up
 * the shared PDO helper without rewriting every include statement.
 */
require_once dirname(__DIR__) . '/../includes/database.php';
?>
