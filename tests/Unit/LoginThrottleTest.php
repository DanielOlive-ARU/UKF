<?php
/**
 * Unit tests for login throttling.
 * Tests SEC-5: Session security / brute force protection.
 * 
 * Run with: C:\xampp\php\php.exe vendor\bin\phpunit.phar tests\Unit\LoginThrottleTest.php
 */

use PHPUnit\Framework\TestCase;

class LoginThrottleTest extends TestCase
{
    private string $testKey;

    protected function setUp(): void
    {
        $this->testKey = 'test_user_' . uniqid();
        LoginThrottle::clear($this->testKey);
    }

    protected function tearDown(): void
    {
        LoginThrottle::clear($this->testKey);
    }

    /**
     * Test that new keys are not locked.
     */
    public function testNewKeyIsNotLocked(): void
    {
        $this->assertFalse(LoginThrottle::isLocked($this->testKey));
    }

    /**
     * Test that 4 failures do not trigger lockout.
     */
    public function testFourFailuresDoNotLock(): void
    {
        for ($i = 0; $i < 4; $i++) {
            LoginThrottle::registerFailure($this->testKey);
        }
        
        $this->assertFalse(LoginThrottle::isLocked($this->testKey));
    }

    /**
     * Test that 5 failures trigger lockout.
     * Requirement: 5 consecutive failures → 60s lockout
     */
    public function testFiveFailuresTriggerLockout(): void
    {
        for ($i = 0; $i < 5; $i++) {
            LoginThrottle::registerFailure($this->testKey);
        }
        
        $this->assertTrue(LoginThrottle::isLocked($this->testKey));
    }

    /**
     * Test that clear() resets the failure count.
     */
    public function testClearResetsFailures(): void
    {
        for ($i = 0; $i < 5; $i++) {
            LoginThrottle::registerFailure($this->testKey);
        }
        $this->assertTrue(LoginThrottle::isLocked($this->testKey));
        
        LoginThrottle::clear($this->testKey);
        
        $this->assertFalse(LoginThrottle::isLocked($this->testKey));
    }

    /**
     * Test makeKey generates consistent keys.
     * Note: This implementation is session-scoped, not per-username.
     */
    public function testMakeKeyConsistency(): void
    {
        $key1 = LoginThrottle::makeKey('testuser');
        $key2 = LoginThrottle::makeKey('testuser');
        
        $this->assertEquals($key1, $key2);
    }

    /**
     * Test makeKey returns 'session' (session-scoped throttling).
     * The throttle applies to the entire session, preventing attackers
     * from trying different usernames to bypass limits.
     */
    public function testMakeKeyIsSessionScoped(): void
    {
        $key = LoginThrottle::makeKey('any_user');
        
        // Session-scoped throttle always returns 'session'
        $this->assertEquals('session', $key);
    }
}
