<?php
/**
 * Minimal CSRF helper shared by both apps.
 * Generates per-context tokens stored in the session and validates submissions.
 */
class Csrf
{
    /** @var string */
    private const SESSION_KEY = '_csrf_tokens';

    /** Ensure a session exists before touching $_SESSION. */
    private static function ensureSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
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
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = array();
        }
        if (empty($_SESSION[self::SESSION_KEY][$context])) {
            $_SESSION[self::SESSION_KEY][$context] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY][$context];
    }

    /**
     * Validate a submitted token for the given context.
     * @param string $value
     * @param string $context
     * @return bool
     */
    public static function validate($value, $context = 'default')
    {
        self::ensureSession();
        if (!is_string($value) || $value === '') {
            return false;
        }
        if (empty($_SESSION[self::SESSION_KEY][$context])) {
            return false;
        }
        return hash_equals($_SESSION[self::SESSION_KEY][$context], $value);
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
}
