<?php
/**
 * Minimal CSRF helper shared by both apps.
 * Generates per-context tokens stored in the session and validates submissions.
 */
class Csrf
{
    /** @var string */
    private const SESSION_KEY = '_csrf_tokens';
    private const MAX_TOKENS_PER_CONTEXT = 20;
    private const TOKEN_TTL_SECONDS = 900; // 15 minutes

    /** Ensure a session exists before touching $_SESSION. */
    private static function ensureSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    private static function &contextBucket($context)
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = array();
        }
        if (!isset($_SESSION[self::SESSION_KEY][$context]) || !is_array($_SESSION[self::SESSION_KEY][$context])) {
            $_SESSION[self::SESSION_KEY][$context] = array();
        }

        self::pruneExpired($context);
        return $_SESSION[self::SESSION_KEY][$context];
    }

    private static function pruneExpired($context): void
    {
        if (empty($_SESSION[self::SESSION_KEY][$context]) || !is_array($_SESSION[self::SESSION_KEY][$context])) {
            return;
        }

        $now = time();
        $_SESSION[self::SESSION_KEY][$context] = array_values(array_filter(
            $_SESSION[self::SESSION_KEY][$context],
            function ($entry) use ($now) {
                if (!is_array($entry) || !isset($entry['value'], $entry['created'])) {
                    return false;
                }
                return ($now - $entry['created']) <= self::TOKEN_TTL_SECONDS;
            }
        ));
    }

    private static function enforceLimit($context): void
    {
        if (empty($_SESSION[self::SESSION_KEY][$context]) || !is_array($_SESSION[self::SESSION_KEY][$context])) {
            return;
        }

        while (count($_SESSION[self::SESSION_KEY][$context]) > self::MAX_TOKENS_PER_CONTEXT) {
            array_shift($_SESSION[self::SESSION_KEY][$context]);
        }
    }

    /**
     * Fetch or create a CSRF token for the given context name.
     * @param string $context
     * @return string
     */
    public static function token($context = 'default')
    {
        self::ensureSession();
        $bucket =& self::contextBucket($context);
        $token = bin2hex(random_bytes(32));
        $bucket[] = array('value' => $token, 'created' => time());
        self::enforceLimit($context);
        return $token;
    }

    /**
     * Validate a submitted token for the given context.
    * @param mixed $value
     * @param string $context
     * @return bool
     */
    public static function validate($value, $context = 'default')
    {
        self::ensureSession();
        if (!is_string($value) || $value === '') {
            return false;
        }
        if (empty($_SESSION[self::SESSION_KEY][$context]) || !is_array($_SESSION[self::SESSION_KEY][$context])) {
            return false;
        }
        self::pruneExpired($context);
        if (empty($_SESSION[self::SESSION_KEY][$context])) {
            return false;
        }

        foreach ($_SESSION[self::SESSION_KEY][$context] as $index => $entry) {
            if (!is_array($entry) || !isset($entry['value'])) {
                continue;
            }
            if (hash_equals($entry['value'], $value)) {
                array_splice($_SESSION[self::SESSION_KEY][$context], $index, 1);
                return true;
            }
        }

        return false;
    }

    /**
     * Convenience helper for embedding hidden inputs in forms.
     * @param string $context
     * @return string
     */
    public static function field($context = 'default')
    {
        $token = self::token($context);
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /** Clear all stored CSRF tokens (e.g., during logout). */
    public static function reset(): void
    {
        self::ensureSession();
        unset($_SESSION[self::SESSION_KEY]);
    }
}
