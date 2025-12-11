<?php
/**
 * Integration tests for authentication flow.
 * Tests SEC-1, SEC-5: Authentication and session security.
 * 
 * Run with: C:\xampp\php\php.exe vendor\bin\phpunit.phar tests\Integration\AuthenticationTest.php
 */

use PHPUnit\Framework\TestCase;

class AuthenticationTest extends TestCase
{
    private string $testUsername;
    private string $testPassword;
    private ?int $testUserId = null;

    protected function setUp(): void
    {
        if (defined('USE_DATABASE_STUB') && USE_DATABASE_STUB) {
            $this->markTestSkipped('Database stub active');
        }
        $this->testUsername = 'phpunit_test_' . uniqid();
        $this->testPassword = 'TestPassword123!';
        
        // Create a test user with bcrypt password
        $hash = hashPassword($this->testPassword);
        Database::query(
            "INSERT INTO wh_users (username, password, role) VALUES (:username, :password, :role)",
            [':username' => $this->testUsername, ':password' => $hash, ':role' => 'clerk']
        );
        $this->testUserId = (int)Database::connection()->lastInsertId();
    }

    protected function tearDown(): void
    {
        if ($this->testUserId) {
            Database::query("DELETE FROM wh_users WHERE id = :id", [':id' => $this->testUserId]);
        }
    }

    /**
     * Test valid credentials authenticate successfully.
     */
    public function testValidCredentialsAuthenticate(): void
    {
        $user = Database::fetchOne(
            "SELECT id, username, password, role FROM wh_users WHERE username = :username",
            [':username' => $this->testUsername]
        );
        
        $this->assertNotNull($user);
        
        $result = verifyPassword($this->testPassword, $user['password']);
        
        $this->assertTrue($result['valid']);
        $this->assertFalse($result['needs_rehash']); // Already bcrypt
    }

    /**
     * Test invalid password is rejected.
     */
    public function testInvalidPasswordRejected(): void
    {
        $user = Database::fetchOne(
            "SELECT password FROM wh_users WHERE username = :username",
            [':username' => $this->testUsername]
        );
        
        $result = verifyPassword('WrongPassword', $user['password']);
        
        $this->assertFalse($result['valid']);
    }

    /**
     * Test non-existent user returns null.
     */
    public function testNonExistentUserReturnsNull(): void
    {
        $user = Database::fetchOne(
            "SELECT * FROM wh_users WHERE username = :username",
            [':username' => 'definitely_not_a_real_user_' . uniqid()]
        );
        
        $this->assertNull($user);
    }

    /**
     * Test MD5 password triggers rehash flag.
     * Requirement: SEC-1 - Opportunistic rehashing
     */
    public function testMd5PasswordTriggersRehash(): void
    {
        $md5Username = 'md5_test_' . uniqid();
        $md5Password = 'LegacyPassword';
        $md5Hash = md5($md5Password);
        
        Database::query(
            "INSERT INTO wh_users (username, password, role) VALUES (:username, :password, :role)",
            [':username' => $md5Username, ':password' => $md5Hash, ':role' => 'clerk']
        );
        $md5UserId = Database::connection()->lastInsertId();
        
        try {
            $user = Database::fetchOne(
                "SELECT password FROM wh_users WHERE username = :username",
                [':username' => $md5Username]
            );
            
            $result = verifyPassword($md5Password, $user['password']);
            
            $this->assertTrue($result['valid']);
            $this->assertTrue($result['needs_rehash']);
        } finally {
            Database::query("DELETE FROM wh_users WHERE id = :id", [':id' => $md5UserId]);
        }
    }

    /**
     * Test opportunistic rehash updates password in database.
     */
    public function testOpportunisticRehashUpdatesDatabase(): void
    {
        $md5Username = 'rehash_test_' . uniqid();
        $md5Password = 'RehashMe';
        $md5Hash = md5($md5Password);
        
        Database::query(
            "INSERT INTO wh_users (username, password, role) VALUES (:username, :password, :role)",
            [':username' => $md5Username, ':password' => $md5Hash, ':role' => 'clerk']
        );
        $userId = Database::connection()->lastInsertId();
        
        try {
            // Simulate login: verify and rehash
            $user = Database::fetchOne(
                "SELECT id, password FROM wh_users WHERE username = :username",
                [':username' => $md5Username]
            );
            
            $result = verifyPassword($md5Password, $user['password']);
            $this->assertTrue($result['needs_rehash']);
            
            // Perform rehash (as login.php does)
            $newHash = hashPassword($md5Password);
            Database::query(
                "UPDATE wh_users SET password = :password WHERE id = :id",
                [':password' => $newHash, ':id' => $user['id']]
            );
            
            // Verify new hash is bcrypt
            $updatedUser = Database::fetchOne(
                "SELECT password FROM wh_users WHERE id = :id",
                [':id' => $user['id']]
            );
            
            $this->assertStringStartsWith('$2y$', $updatedUser['password']);
            
            // Verify password still works with new hash
            $newResult = verifyPassword($md5Password, $updatedUser['password']);
            $this->assertTrue($newResult['valid']);
            $this->assertFalse($newResult['needs_rehash']);
            
        } finally {
            Database::query("DELETE FROM wh_users WHERE id = :id", [':id' => $userId]);
        }
    }
}
