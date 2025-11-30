<?php
/**
 * Simple session-scoped login throttling helper.
 * Tracks failed attempts per session and enforces a short lockout.
 */
class LoginThrottle
{
    private const SESSION_KEY = '_login_attempts';
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 60;

    private static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function makeKey(string $unusedUsername): string
    {
        return 'session';
    }

    private static function &bucket()
    {
        self::ensureSession();
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = array('failures' => 0, 'lockout_until' => 0);
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function isLocked(string $unusedKey): bool
    {
        $bucket =& self::bucket();
        $now = time();
        if (!empty($bucket['lockout_until']) && $bucket['lockout_until'] > $now) {
            return true;
        }

        if (!empty($bucket['lockout_until']) && $bucket['lockout_until'] <= $now) {
            $bucket = array('failures' => 0, 'lockout_until' => 0);
        }

        return false;
    }

    public static function registerFailure(string $unusedKey): void
    {
        $bucket =& self::bucket();
        $now = time();

        if (!empty($bucket['lockout_until']) && $bucket['lockout_until'] <= $now) {
            $bucket = array('failures' => 0, 'lockout_until' => 0);
        }

        $bucket['failures'] = isset($bucket['failures']) ? $bucket['failures'] + 1 : 1;
        if ($bucket['failures'] >= self::MAX_ATTEMPTS) {
            $bucket['lockout_until'] = $now + self::LOCKOUT_SECONDS;
        }
    }

    public static function clear(string $unusedKey): void
    {
        $bucket =& self::bucket();
        $bucket = array('failures' => 0, 'lockout_until' => 0);
    }
}
