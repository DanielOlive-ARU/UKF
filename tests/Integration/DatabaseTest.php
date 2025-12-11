<?php
/**
 * Integration tests for Database helper.
 * Tests SEC-4: Prepared statements, REL-1: ACID transactions.
 * 
 * Requires a running database with stocktrackpro schema.
 * Run with: C:\xampp\php\php.exe vendor\bin\phpunit.phar tests\Integration\DatabaseTest.php
 */

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        if (defined('USE_DATABASE_STUB') && USE_DATABASE_STUB) {
            $this->markTestSkipped('Database stub active');
        }
    }

    /**
     * Test that connection returns a PDO instance.
     */
    public function testConnectionReturnsPdo(): void
    {
        $pdo = Database::connection();
        
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    /**
     * Test that connection is reused (singleton pattern).
     */
    public function testConnectionIsSingleton(): void
    {
        $pdo1 = Database::connection();
        $pdo2 = Database::connection();
        
        $this->assertSame($pdo1, $pdo2);
    }

    /**
     * Test query with prepared statement.
     * Requirement: SEC-4 - Prepared statements preventing SQL injection
     */
    public function testQueryWithPreparedStatement(): void
    {
        $result = Database::query(
            "SELECT 1 + 1 AS sum, :param AS param",
            [':param' => 'test_value']
        );
        
        $row = $result->fetch();
        
        $this->assertEquals(2, $row['sum']);
        $this->assertEquals('test_value', $row['param']);
    }

    /**
     * Test fetchOne returns single row.
     */
    public function testFetchOneReturnsSingleRow(): void
    {
        $row = Database::fetchOne(
            "SELECT :val AS value",
            [':val' => 'hello']
        );
        
        $this->assertIsArray($row);
        $this->assertEquals('hello', $row['value']);
    }

    /**
     * Test fetchOne returns null when no rows.
     */
    public function testFetchOneReturnsNullWhenEmpty(): void
    {
        $row = Database::fetchOne(
            "SELECT * FROM products WHERE id = :id",
            [':id' => -999999]
        );
        
        $this->assertNull($row);
    }

    /**
     * Test transaction commits on success.
     * Requirement: REL-1 - ACID transactions
     */
    public function testTransactionCommitsOnSuccess(): void
    {
        // Create a unique SKU for this test
        $testSku = 'TEST_TXN_' . uniqid();
        
        $result = Database::transaction(function () use ($testSku) {
            Database::query(
                "INSERT INTO products (sku, name, price, stock) VALUES (:sku, :name, :price, :stock)",
                [':sku' => $testSku, ':name' => 'Transaction Test', ':price' => 1.00, ':stock' => 0]
            );
            return Database::connection()->lastInsertId();
        });
        
        $this->assertGreaterThan(0, $result);
        
        // Verify it was committed
        $row = Database::fetchOne("SELECT * FROM products WHERE sku = :sku", [':sku' => $testSku]);
        $this->assertNotNull($row);
        
        // Cleanup
        Database::query("DELETE FROM products WHERE sku = :sku", [':sku' => $testSku]);
    }

    /**
     * Test transaction rolls back on exception.
     * Requirement: REL-1 - ACID transactions
     */
    public function testTransactionRollsBackOnException(): void
    {
        $testSku = 'TEST_ROLLBACK_' . uniqid();
        
        try {
            Database::transaction(function () use ($testSku) {
                Database::query(
                    "INSERT INTO products (sku, name, price, stock) VALUES (:sku, :name, :price, :stock)",
                    [':sku' => $testSku, ':name' => 'Rollback Test', ':price' => 1.00, ':stock' => 0]
                );
                throw new RuntimeException('Simulated failure');
            });
            $this->fail('Expected exception was not thrown');
        } catch (RuntimeException $e) {
            $this->assertEquals('Simulated failure', $e->getMessage());
        }
        
        // Verify it was rolled back
        $row = Database::fetchOne("SELECT * FROM products WHERE sku = :sku", [':sku' => $testSku]);
        $this->assertNull($row);
    }

    /**
     * Test charset is utf8mb4.
     * Requirement: REL-2 - UTF8MB4 charset
     */
    public function testCharsetIsUtf8mb4(): void
    {
        $row = Database::fetchOne("SELECT @@character_set_connection AS charset");
        
        $this->assertEquals('utf8mb4', $row['charset']);
    }

    /**
     * Test SQL injection is prevented by prepared statements.
     * Requirement: SEC-4 - SQL injection prevention
     */
    public function testSqlInjectionPrevented(): void
    {
        // This malicious input should be treated as a literal string, not SQL
        $maliciousInput = "'; DROP TABLE products; --";
        
        $row = Database::fetchOne(
            "SELECT :input AS safe_value",
            [':input' => $maliciousInput]
        );
        
        $this->assertEquals($maliciousInput, $row['safe_value']);
        
        // Verify products table still exists
        $count = Database::fetchOne("SELECT COUNT(*) as cnt FROM products");
        $this->assertGreaterThanOrEqual(0, $count['cnt']);
    }
}
