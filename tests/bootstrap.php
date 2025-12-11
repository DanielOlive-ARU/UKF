<?php
/**
 * PHPUnit bootstrap file.
 * Loads the application autoloader and test configuration.
 */

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session for CSRF tests
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Allow env flags to set test hooks
if (!defined('USE_DATABASE_STUB') && getenv('USE_DATABASE_STUB')) {
    define('USE_DATABASE_STUB', true);
}
if (!defined('TEST_BYPASS_AUTH') && getenv('TEST_BYPASS_AUTH')) {
    define('TEST_BYPASS_AUTH', true);
}

// Load the shared includes (allow stubbing Database in tests by defining USE_DATABASE_STUB)
if (!defined('USE_DATABASE_STUB')) {
    require_once dirname(__DIR__) . '/includes/database.php';
} elseif (!class_exists('Database')) {
    /**
     * Simple delegating stub to avoid touching a real database during tests.
     * Tests can inject a delegate object with matching methods.
     */
    class Database
    {
        /** @var object|null */
        private static $delegate = null;

        public static function setDelegate($delegate): void
        {
            self::$delegate = $delegate;
        }

        public static function getDelegate()
        {
            return self::$delegate;
        }

        public static function __callStatic($name, $arguments)
        {
            if (!self::$delegate || !method_exists(self::$delegate, $name)) {
                throw new RuntimeException('Database stub delegate not set for method: ' . $name);
            }
            return self::$delegate->$name(...$arguments);
        }
    }
}
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/password.php';
require_once dirname(__DIR__) . '/includes/login_throttle.php';
