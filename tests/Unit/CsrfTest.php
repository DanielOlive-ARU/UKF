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
}
