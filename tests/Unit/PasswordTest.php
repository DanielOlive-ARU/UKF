<?php
/**
 * Unit tests for password hashing and verification functions.
 * Tests SEC-1: Modern authentication requirement.
 * 
 * Run with: C:\xampp\php\php.exe vendor\bin\phpunit.phar tests\Unit\PasswordTest.php
 */

use PHPUnit\Framework\TestCase;

class PasswordTest extends TestCase
{
    /**
     * Test that hashPassword returns a valid bcrypt hash.
     * Requirement: SEC-1 - Modern authentication
     */
    public function testHashPasswordReturnsBcryptHash(): void
    {
        $password = 'testPassword123';
        $hash = hashPassword($password);
        
        // Bcrypt hashes start with $2y$ and are 60 characters
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertEquals(60, strlen($hash));
    }

    /**
     * Test that hashPassword generates unique hashes for same password.
     * Ensures proper salt usage.
     */
    public function testHashPasswordGeneratesUniqueSalts(): void
    {
        $password = 'samePassword';
        $hash1 = hashPassword($password);
        $hash2 = hashPassword($password);
        
        // Same password should produce different hashes due to unique salts
        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * Test verifyPassword with correct bcrypt password.
     */
    public function testVerifyPasswordWithCorrectBcryptPassword(): void
    {
        $password = 'correctPassword';
        $hash = hashPassword($password);
        
        $result = verifyPassword($password, $hash);
        
        $this->assertTrue($result['valid']);
        $this->assertFalse($result['needs_rehash']);
    }

    /**
     * Test verifyPassword with incorrect password.
     */
    public function testVerifyPasswordWithIncorrectPassword(): void
    {
        $password = 'correctPassword';
        $wrongPassword = 'wrongPassword';
        $hash = hashPassword($password);
        
        $result = verifyPassword($wrongPassword, $hash);
        
        $this->assertFalse($result['valid']);
        $this->assertFalse($result['needs_rehash']);
    }

    /**
     * Test verifyPassword detects legacy MD5 hash and flags for rehash.
     * Requirement: SEC-1 - Opportunistic rehashing from MD5
     */
    public function testVerifyPasswordWithLegacyMd5Hash(): void
    {
        $password = 'legacyPassword';
        $md5Hash = md5($password); // Simulate legacy MD5 hash
        
        $result = verifyPassword($password, $md5Hash);
        
        $this->assertTrue($result['valid']);
        $this->assertTrue($result['needs_rehash']); // Should flag for upgrade
    }

    /**
     * Test verifyPassword rejects incorrect MD5 password.
     */
    public function testVerifyPasswordRejectsWrongMd5Password(): void
    {
        $password = 'legacyPassword';
        $md5Hash = md5($password);
        
        $result = verifyPassword('wrongPassword', $md5Hash);
        
        $this->assertFalse($result['valid']);
        $this->assertFalse($result['needs_rehash']);
    }

    /**
     * Test MD5 detection pattern (exactly 32 hex characters).
     */
    public function testMd5DetectionPattern(): void
    {
        // Valid MD5
        $md5 = '5f4dcc3b5aa765d61d8327deb882cf99'; // md5('password')
        $this->assertEquals(32, strlen($md5));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/i', $md5);
        
        // Bcrypt should NOT match MD5 pattern
        $bcrypt = hashPassword('password');
        $this->assertDoesNotMatchRegularExpression('/^[a-f0-9]{32}$/i', $bcrypt);
    }

    /**
     * Test empty password handling.
     */
    public function testEmptyPasswordHandling(): void
    {
        $hash = hashPassword('realPassword');
        
        $result = verifyPassword('', $hash);
        
        $this->assertFalse($result['valid']);
    }
}
