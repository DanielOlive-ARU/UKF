<?php
/**
 * Unit tests for CSRF protection.
 * Tests SEC-3: CSRF protection requirement.
 * 
 * Run with: C:\xampp\php\php.exe vendor\bin\phpunit.phar tests\Unit\CsrfTest.php
 */

use PHPUnit\Framework\TestCase;

class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear session CSRF tokens before each test
        if (isset($_SESSION['_csrf_tokens'])) {
            unset($_SESSION['_csrf_tokens']);
        }
    }

    /**
     * Test that token generates a 64-character hex string.
     */
    public function testTokenGeneratesValidHexString(): void
    {
        $token = Csrf::token('test_context');
        
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    /**
     * Test that each token call generates a unique token.
     */
    public function testTokenGeneratesUniqueTokens(): void
    {
        $token1 = Csrf::token('test_context');
        $token2 = Csrf::token('test_context');
        
        $this->assertNotEquals($token1, $token2);
    }

    /**
     * Test that validate accepts a valid token.
     */
    public function testValidateAcceptsValidToken(): void
    {
        $token = Csrf::token('form_context');
        
        $result = Csrf::validate($token, 'form_context');
        
        $this->assertTrue($result);
    }

    /**
     * Test that validate rejects an invalid token.
     */
    public function testValidateRejectsInvalidToken(): void
    {
        Csrf::token('form_context'); // Generate a token
        
        $result = Csrf::validate('invalid_token_value', 'form_context');
        
        $this->assertFalse($result);
    }

    /**
     * Test that validate rejects token from different context.
     */
    public function testValidateRejectsWrongContext(): void
    {
        $token = Csrf::token('context_a');
        
        $result = Csrf::validate($token, 'context_b');
        
        $this->assertFalse($result);
    }

    /**
     * Test that tokens are single-use (consumed after validation).
     */
    public function testTokensAreSingleUse(): void
    {
        $token = Csrf::token('single_use_test');
        
        // First validation should succeed
        $firstResult = Csrf::validate($token, 'single_use_test');
        $this->assertTrue($firstResult);
        
        // Second validation with same token should fail
        $secondResult = Csrf::validate($token, 'single_use_test');
        $this->assertFalse($secondResult);
    }

    /**
     * Test that field() returns valid HTML input.
     */
    public function testFieldReturnsHtmlInput(): void
    {
        $field = Csrf::field('html_test');
        
        $this->assertStringContainsString('<input', $field);
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString('value="', $field);
    }

    /**
     * Test empty token is rejected.
     */
    public function testEmptyTokenRejected(): void
    {
        Csrf::token('test');
        
        $this->assertFalse(Csrf::validate('', 'test'));
    }

    /**
     * Expired tokens are pruned and rejected.
     */
    public function testExpiredTokenIsPrunedAndRejected(): void
    {
        $_SESSION['_csrf_tokens']['expired_ctx'][] = array(
            'value'   => 'expired-token',
            'created' => time() - 2000 // older than TTL
        );

        $this->assertFalse(Csrf::validate('expired-token', 'expired_ctx'));
        $this->assertTrue(empty($_SESSION['_csrf_tokens']['expired_ctx']));
    }

    /**
     * Token bucket is capped at MAX_TOKENS_PER_CONTEXT.
     */
    public function testTokenBucketIsCapped(): void
    {
        for ($i = 0; $i < 25; $i++) {
            Csrf::token('cap_ctx');
        }

        $this->assertLessThanOrEqual(20, count($_SESSION['_csrf_tokens']['cap_ctx']));
    }

    /**
     * Reset clears all stored tokens for every context.
     */
    public function testResetClearsAllTokens(): void
    {
        Csrf::token('ctx_a');
        Csrf::token('ctx_b');

        Csrf::reset();

        $this->assertArrayNotHasKey('_csrf_tokens', $_SESSION);
    }

    /**
     * ensureSession should start a session if closed.
     */
    public function testEnsureSessionStartsWhenClosed(): void
    {
        session_write_close();
        $this->assertNotEquals(PHP_SESSION_ACTIVE, session_status());

        Csrf::token('session_restart_ctx');

        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    /**
     * Malformed entries are skipped and valid token still passes.
     */
    public function testValidateSkipsMalformedEntries(): void
    {
        $_SESSION['_csrf_tokens']['mixed_ctx'] = array(
            array('created' => time()), // missing value
            array('value' => 'good', 'created' => time())
        );

        $this->assertTrue(Csrf::validate('good', 'mixed_ctx'));
    }

    /**
     * Expired tokens are dropped while fresh tokens remain valid.
     */
    public function testPruneExpiredDropsOldKeepsNew(): void
    {
        $_SESSION['_csrf_tokens']['mixed_time'] = array(
            array('value' => 'old', 'created' => time() - 2000),
            array('value' => 'new', 'created' => time())
        );

        $this->assertTrue(Csrf::validate('new', 'mixed_time'));
        $this->assertFalse(Csrf::validate('old', 'mixed_time'));
    }

    /**
     * context bucket is re-initialized when session data is malformed.
     */
    public function testContextBucketResetsMalformedSessionData(): void
    {
        $_SESSION['_csrf_tokens'] = 'not-an-array';

        Csrf::token('fix_ctx');

        $this->assertIsArray($_SESSION['_csrf_tokens']);
        $this->assertArrayHasKey('fix_ctx', $_SESSION['_csrf_tokens']);
    }

    /**
     * Validate returns false when context is empty.
     */
    public function testValidateReturnsFalseWhenContextEmpty(): void
    {
        unset($_SESSION['_csrf_tokens']);

        $this->assertFalse(Csrf::validate('anything', 'missing_ctx'));
    }

    /**
     * Valid tokens are consumed; other tokens in the same context remain.
     */
    public function testValidateConsumesOnlyMatchedToken(): void
    {
        $tokenA = Csrf::token('multi_ctx');
        $tokenB = Csrf::token('multi_ctx');

        $this->assertTrue(Csrf::validate($tokenA, 'multi_ctx'));
        $this->assertTrue(Csrf::validate($tokenB, 'multi_ctx'));
    }

    /**
     * When more than MAX_TOKENS_PER_CONTEXT are created, the oldest is dropped.
     */
    public function testOldestTokenDroppedWhenOverLimit(): void
    {
        $first = Csrf::token('cap_limit');
        for ($i = 0; $i < 25; $i++) {
            Csrf::token('cap_limit');
        }

        $this->assertLessThanOrEqual(20, count($_SESSION['_csrf_tokens']['cap_limit']));
        $this->assertFalse(Csrf::validate($first, 'cap_limit'));
    }
}
