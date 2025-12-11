<?php

use PHPUnit\Framework\TestCase;

/**
 * Exercise WarehouseProLite/label_print.php without hitting a real database.
 * Uses a Database stub and auth bypass hook.
 */
class LabelPrintTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('USE_DATABASE_STUB')) {
            define('USE_DATABASE_STUB', true);
        }
        if (!defined('TEST_BYPASS_AUTH')) {
            define('TEST_BYPASS_AUTH', true);
        }
        Database::setDelegate(new FakeDatabase());
    }

    protected function setUp(): void
    {
        $_SESSION = array();
        $_GET = array();
        $_POST = array();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        FakeDatabase::reset();
    }

    /**
     * Helper to render the page and capture output.
     */
    private function renderPage(): string
    {
        ob_start();
        include dirname(__DIR__, 3) . '/WarehouseProLite/label_print.php';
        return ob_get_clean();
    }

    public function testCsrfFailureShowsNotice(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array('product_id' => 1, 'copies' => 1, 'csrf_token' => 'bad');

        $html = $this->renderPage();

        $this->assertStringContainsString('Session expired', $html);
    }

    public function testInvalidCopiesShowsNotice(): void
    {
        $token = Csrf::token('wh_label_print');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array('product_id' => 1, 'copies' => 0, 'csrf_token' => $token);

        $html = $this->renderPage();

        $this->assertStringContainsString('Copies must be between 1 and 99', $html);
    }

    public function testMissingProductShowsNotice(): void
    {
        $token = Csrf::token('wh_label_print');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array('product_id' => 0, 'copies' => 1, 'csrf_token' => $token);

        $html = $this->renderPage();

        $this->assertStringContainsString('Select a product before generating labels', $html);
    }

    public function testProductNotFoundShowsNotice(): void
    {
        $token = Csrf::token('wh_label_print');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array('product_id' => 999, 'copies' => 1, 'csrf_token' => $token);

        $html = $this->renderPage();

        $this->assertStringContainsString('Product not found', $html);
    }

    public function testHappyPathRendersLabelData(): void
    {
        $token = Csrf::token('wh_label_print');
        $db = Database::getDelegate();
        $db->products = array(
            1 => array(
                'id' => 1,
                'sku' => 'ABC-123',
                'name' => 'Test Product',
                'country_iso' => 'GB',
                'class' => 'A',
                'pack_uom' => 'BOX',
                'default_pack_weight_g' => 500,
                'best_before_days' => 2
            )
        );
        $db->printerNames = array('Zebra-1', 'Zebra-2');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array('product_id' => 1, 'copies' => 3, 'csrf_token' => $token);

        $html = $this->renderPage();

        $this->assertStringContainsString('Test Product', $html);
        $this->assertStringContainsString('ABC-123', $html);
        $expectedBestBefore = date('d/m/Y', strtotime('+2 days'));
        $this->assertStringContainsString($expectedBestBefore, $html);
        $this->assertStringContainsString('Zebra-1', $html);
        $this->assertStringContainsString('Zebra-2', $html);
    }
}

/**
 * Lightweight stub for Database used by WarehouseProLite pages during tests.
 */
class FakeDatabase
{
    public array $products = array();
    public array $printerNames = array();

    public static function reset(): void
    {
        Database::setDelegate(new self());
    }

    public static function query($sql, $params = array())
    {
        $sqlUpper = strtoupper($sql);
        if (strpos($sqlUpper, 'SELECT DISTINCT PRINTER_NAME') !== false) {
            return new FakeStatement(Database::getDelegate()->printerNames);
        }
        if (strpos($sqlUpper, 'SELECT P.ID') !== false) {
            return new FakeStatement(array_values(Database::getDelegate()->products));
        }
        return new FakeStatement(array());
    }

    public static function fetchOne($sql, $params = array())
    {
        $sqlUpper = strtoupper($sql);
        if (strpos($sqlUpper, 'COUNT(*)') !== false) {
            return array('total' => count(Database::getDelegate()->products));
        }
        if (isset($params[':id'])) {
            $id = (int)$params[':id'];
            return Database::getDelegate()->products[$id] ?? null;
        }
        return null;
    }
}

class FakeStatement
{
    /** @var array */
    private $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function fetchAll($mode = null)
    {
        return $this->rows;
    }
}
