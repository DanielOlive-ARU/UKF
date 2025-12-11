<?php
/**
 * Password helper functions for secure password hashing and verification.
 * Supports opportunistic rehashing from legacy MD5 to bcrypt.
 */

/**
 * Hash a password using bcrypt (PASSWORD_DEFAULT).
 *
 * @param string $password The plain-text password.
 * @return string The bcrypt hash.
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a password against a stored hash.
 * Supports legacy MD5 hashes (32-char hex) and bcrypt.
 *
 * @param string $input      The plain-text password from the user.
 * @param string $storedHash The hash from the database.
 * @return array ['valid' => bool, 'needs_rehash' => bool]
 */
function verifyPassword($input, $storedHash)
{
    // Detect legacy MD5 hash (exactly 32 hex characters)
    if (preg_match('/^[a-f0-9]{32}$/i', $storedHash)) {
        // Legacy MD5 comparison
        $valid = hash_equals($storedHash, md5($input));
        return array(
            'valid'        => $valid,
            'needs_rehash' => $valid // If valid, needs upgrade to bcrypt
        );
    }

    // Modern bcrypt/argon verification
    $valid = password_verify($input, $storedHash);
    return array(
        'valid'        => $valid,
        'needs_rehash' => $valid && password_needs_rehash($storedHash, PASSWORD_DEFAULT)
    );
}
