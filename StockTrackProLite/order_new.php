<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

$notice = '';

/* ---------------- Handle POST save ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    $rawItems = isset($_POST['item']) && is_array($_POST['item']) ? $_POST['item'] : array();

    $items = array(); // normalized [product_id => qty]
    foreach ($rawItems as $pid => $qty) {
        $pid = (int)$pid;
        $qty = (int)$qty;
        if ($pid > 0 && $qty > 0) {
            $items[$pid] = $qty;
        }
    }

    if ($customerId && $items) {
        try {
            $orderId = Database::transaction(function () use ($customerId, $items) {
                $productIds = array_keys($items);
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));

                // Lock product rows so stock math stays consistent during the order.
                $rows = Database::query(
                    "SELECT id, price, stock FROM products WHERE id IN ($placeholders) FOR UPDATE",
                    $productIds
                )->fetchAll();

                $productMap = array();
                foreach ($rows as $row) {
                    $productMap[$row['id']] = $row;
                }

                if (count($productMap) !== count($items)) {
                    throw new RuntimeException('One or more products could not be found.');
                }

                $total = 0;
                $lines = array();
                foreach ($items as $pid => $qty) {
                    $product = $productMap[$pid];
                    if ((int)$product['stock'] < $qty) {
                        throw new RuntimeException('Insufficient stock for one of the selected items.');
                    }

                    $price = (float)$product['price'];
                    $total += $price * $qty;
                    $lines[] = array(
                        'product_id' => $pid,
                        'quantity' => $qty,
                        'price' => $price
                    );

                    Database::query(
                        "UPDATE products SET stock = stock - :qty WHERE id = :id",
                        array(':qty' => $qty, ':id' => $pid)
                    );
                }

                if ($total <= 0) {
                    throw new RuntimeException('No valid order lines were supplied.');
                }

                Database::query(
                    "INSERT INTO orders (customer_id, order_date, total) VALUES (:customer_id, NOW(), :total)",
                    array(
                        ':customer_id' => $customerId,
                        ':total' => $total
                    )
                );

                $orderId = Database::connection()->lastInsertId();

                foreach ($lines as $line) {
                    Database::query(
                        "INSERT INTO order_items (product_id, order_id, quantity, price)
                         VALUES (:product_id, :order_id, :quantity, :price)",
                        array(
                            ':product_id' => $line['product_id'],
                            ':order_id' => $orderId,
                            ':quantity' => $line['quantity'],
                            ':price' => $line['price']
                        )
                    );
                }

                return $orderId;
            });

            header("Location: order_view.php?id=" . $orderId);
            exit();
        } catch (RuntimeException $runtimeException) {
            $notice = $runtimeException->getMessage();
        } catch (Exception $exception) {
            $notice = 'Order could not be saved. Please try again.';
        }
    } else {
        $notice = 'Select a customer and at least one item.';
    }
}

/* ---------------- Load customers + products ---------------- */
$customers = Database::query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
$products  = Database::query("SELECT id, name, price, stock FROM products ORDER BY name")->fetchAll();
?>
<h2>New Order</h2>

<?php if ($notice): ?>
    <p class="notice"><?php echo htmlspecialchars($notice); ?></p>
<?php endif; ?>

<form action="order_new.php" method="post">
    <label>Customer:
        <select name="customer_id" required>
            <option value="">-- select --</option>
            <?php foreach ($customers as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <h3>Items</h3>
    <table>
        <thead><tr><th>Product</th><th>Stock</th><th>Price (£)</th><th>Qty</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo $p['stock']; ?></td>
                <td><?php echo number_format($p['price'], 2); ?></td>
                <td>
                    <input type="number" name="item[<?php echo $p['id']; ?>]" value="0" min="0" style="width:60px">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p>
        <input type="submit" value="Save Order">
        <a href="orders.php">Cancel</a>
    </p>
</form>

<?php include 'includes/footer.php'; ?>
