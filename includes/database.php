<?php
/**
 * Lightweight PDO helper shared by both legacy apps.
 * Keeps compatibility with PHP 5.6 while preparing the PHP 8 migration.
 */
class Database
{
    /** @var PDO|null */
    private static $pdo;

    /** @var array|null */
    private static $config;

    /**
     * Load configuration from config/database.php with an optional override file.
     * @return array
     */
    private static function config()
    {
        if (self::$config === null) {
            $base = require dirname(__DIR__) . '/config/database.php';
            $overridePath = dirname(__DIR__) . '/config/database.local.php';
            if (file_exists($overridePath)) {
                $override = require $overridePath;
                $base = array_merge($base, $override);
            }
            self::$config = $base;
        }
        return self::$config;
    }

    /**
     * Lazily build and reuse the PDO connection.
     * @return PDO
     */
    public static function connection()
    {
        if (!self::$pdo) {
            $config = self::config();
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            );
            self::$pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        }
        return self::$pdo;
    }

    /**
     * Prepare and execute a statement with bound parameters.
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public static function query($sql, $params = array())
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Convenience wrapper for statements that should return a single row.
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public static function fetchOne($sql, $params = array())
    {
        $stmt = self::query($sql, $params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Execute a callback inside a database transaction.
     * Rolls back automatically if the callback throws.
     * @param callable $callback
     * @return mixed
     * @throws Exception
     */
    public static function transaction($callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Transaction callback must be callable');
        }

        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $result = call_user_func($callback, $pdo);
            $pdo->commit();
            return $result;
        } catch (Exception $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
}
