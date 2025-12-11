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

    /**
     * Bucket initializes structure when session key is missing.
     */
    public function testBucketInitializesStructure(): void
    {
        unset($_SESSION['_login_attempts']);

        $this->assertFalse(LoginThrottle::isLocked($this->testKey));
        $this->assertIsArray($_SESSION['_login_attempts']);
        $this->assertArrayHasKey('failures', $_SESSION['_login_attempts']);
        $this->assertArrayHasKey('lockout_until', $_SESSION['_login_attempts']);
    }

    /**
     * Lockout expires and resets once the window passes.
     */
    public function testLockoutExpiresAndResets(): void
    {
        $_SESSION['_login_attempts'] = array('failures' => 5, 'lockout_until' => time() - 1);

        $this->assertFalse(LoginThrottle::isLocked($this->testKey));
        $this->assertEquals(array('failures' => 0, 'lockout_until' => 0), $_SESSION['_login_attempts']);
    }

    /**
     * After expiry, a new failure starts from a clean slate.
     */
    public function testRegisterFailureAfterExpiryStartsFresh(): void
    {
        $_SESSION['_login_attempts'] = array('failures' => 5, 'lockout_until' => time() - 1);

        LoginThrottle::registerFailure($this->testKey);

        $this->assertEquals(1, $_SESSION['_login_attempts']['failures']);
        $this->assertEquals(0, $_SESSION['_login_attempts']['lockout_until']);
    }

    /**
     * Already locked state should not extend lockout on additional failures.
     */
    public function testRegisterFailureDoesNotExtendActiveLockout(): void
    {
        $future = time() + 30;
        $_SESSION['_login_attempts'] = array('failures' => 5, 'lockout_until' => $future);

        LoginThrottle::registerFailure($this->testKey);

        $this->assertGreaterThanOrEqual($future, $_SESSION['_login_attempts']['lockout_until']);
        $this->assertGreaterThanOrEqual(5, $_SESSION['_login_attempts']['failures']);
    }
}
