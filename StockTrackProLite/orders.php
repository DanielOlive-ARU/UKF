<?php
include 'includes/db.php';
require_once dirname(__DIR__) . '/includes/database.php';
include 'includes/header.php';

/* Extract search parameters */
$search_id = isset($_GET['search_id']) ? trim($_GET['search_id']) : '';
$search_customer = isset($_GET['search_customer']) ? trim($_GET['search_customer']) : '';
$search_date_from = isset($_GET['search_date_from']) ? trim($_GET['search_date_from']) : '';
$search_date_to = isset($_GET['search_date_to']) ? trim($_GET['search_date_to']) : '';

/* Pagination configuration */
$allowed_per_page = array(10, 20, 50, 100);
$default_per_page = 20;
$per_page = isset($_GET['per_page']) ? (int) $_GET['per_page'] : $default_per_page;
if (!in_array($per_page, $allowed_per_page)) {
    $per_page = $default_per_page;
}
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

/* Build dynamic WHERE clause and params */
$where_parts = array();
$params = array();

if ($search_id !== '') {
    $where_parts[] = 'o.id = :order_id';
    $params[':order_id'] = (int) $search_id;
}

if ($search_customer !== '') {
    $where_parts[] = 'c.name LIKE :customer_name';
    $params[':customer_name'] = '%' . $search_customer . '%';
}

if ($search_date_from !== '') {
    $where_parts[] = 'DATE(o.order_date) >= :date_from';
    $params[':date_from'] = $search_date_from;
}

if ($search_date_to !== '') {
    $where_parts[] = 'DATE(o.order_date) <= :date_to';
    $params[':date_to'] = $search_date_to;
}

$where_clause = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

/* Get total count for pagination */
$count_result = Database::fetchOne(
    "SELECT COUNT(o.id) as cnt FROM orders o
     LEFT JOIN customers c ON c.id = o.customer_id
     $where_clause",
    $params
);
$total_orders = (int) $count_result['cnt'];
$total_pages = ($total_orders > 0) ? ceil($total_orders / $per_page) : 1;

/* Clamp page to valid range */
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

/* Fetch paginated and filtered orders */
$orders = Database::query(
    "SELECT o.id, o.order_date, o.total, c.name AS customer
     FROM orders o
     LEFT JOIN customers c ON c.id = o.customer_id
     $where_clause
     ORDER BY o.order_date DESC
     LIMIT :offset, :limit",
    array_merge($params, array(':offset' => $offset, ':limit' => $per_page))
)->fetchAll();
?>
<h2>Orders</h2>

<p><a href="order_new.php" class="btn">+ New Order</a></p>

<!-- Search Form -->
<div style="background-color: #f5f5f5; padding: 12px; margin-bottom: 15px; border-radius: 4px;">
    <form method="get" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto auto; gap: 10px; align-items: flex-end;">
        <div>
            <label><strong>Order ID</strong></label>
            <input type="text" name="search_id" value="<?php echo htmlspecialchars($search_id); ?>" placeholder="e.g. 1001" style="width: 100%; padding: 6px; box-sizing: border-box;">
        </div>
        <div>
            <label><strong>Customer Name</strong></label>
            <input type="text" name="search_customer" value="<?php echo htmlspecialchars($search_customer); ?>" placeholder="e.g. Acme" style="width: 100%; padding: 6px; box-sizing: border-box;">
        </div>
        <div>
            <label><strong>Date From</strong></label>
            <input type="date" name="search_date_from" value="<?php echo htmlspecialchars($search_date_from); ?>" style="width: 100%; padding: 6px; box-sizing: border-box;">
        </div>
        <div>
            <label><strong>Date To</strong></label>
            <input type="date" name="search_date_to" value="<?php echo htmlspecialchars($search_date_to); ?>" style="width: 100%; padding: 6px; box-sizing: border-box;">
        </div>
        <button type="submit" class="btn" style="margin: 0;">Search</button>
        <a href="orders.php" class="btn" style="margin: 0; text-align: center; text-decoration: none;">Clear</a>
    </form>
</div>

<?php
/* Show search summary */
$has_search = !empty($search_id) || !empty($search_customer) || !empty($search_date_from) || !empty($search_date_to);
if ($has_search) {
    echo '<p style="color: #666;"><strong>Search Results:</strong> ' . count($orders) . ' order' . (count($orders) !== 1 ? 's' : '') . ' found</p>';
}
?>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Total (£)</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
<?php if (!$orders): ?>
        <tr><td colspan="5">No orders yet.</td></tr>
<?php else:
      foreach ($orders as $row): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo date('Y-m-d H:i', strtotime($row['order_date'])); ?></td>
            <td><?php echo htmlspecialchars($row['customer']); ?></td>
            <td><?php echo number_format($row['total'], 2); ?></td>
            <td><a href="order_view.php?id=<?php echo $row['id']; ?>">View</a></td>
        </tr>
<?php endforeach; endif; ?>
    </tbody>
</table>

<?php if ($total_orders > 0): ?>
    <!-- Per-page selector -->
    <div style="margin-top: 15px; margin-bottom: 15px;">
        <form method="get" style="display: inline-block;">
            <?php
            // Preserve search filters
            foreach (array('search_id', 'search_customer', 'search_date_from', 'search_date_to') as $param) {
                $val = isset($_GET[$param]) ? $_GET[$param] : '';
                if ($val !== '') {
                    echo '<input type="hidden" name="' . htmlspecialchars($param) . '" value="' . htmlspecialchars($val) . '">';
                }
            }
            ?>
            <label>Show
                <select name="per_page" onchange="this.form.submit()" style="padding: 4px;">
                    <?php foreach ($allowed_per_page as $v): ?>
                        <option value="<?php echo $v; ?>"<?php if ($per_page == $v) echo ' selected'; ?>><?php echo $v; ?></option>
                    <?php endforeach; ?>
                </select>
            orders per page</label>
            <input type="hidden" name="page" value="1">
        </form>
    </div>

    <?php if ($total_pages > 1): ?>
        <!-- Pagination controls -->
        <div class="pagination" style="margin-top: 12px; text-align: center;">
            <?php
            // Build base query string preserving search filters and per_page
            $base_params = array();
            foreach (array('search_id', 'search_customer', 'search_date_from', 'search_date_to', 'per_page') as $param) {
                $val = isset($_GET[$param]) ? $_GET[$param] : '';
                if ($val !== '' || $param === 'per_page') {
                    $base_params[$param] = $val;
                }
            }
            $base_params['per_page'] = $per_page;
            
            $base = '?';
            foreach ($base_params as $k => $v) {
                if ($v !== '') {
                    $base .= urlencode($k) . '=' . urlencode($v) . '&';
                }
            }
            
            // Previous link
            if ($page > 1) {
                echo '<a href="' . htmlspecialchars($base . 'page=' . ($page - 1)) . '" style="margin-right: 5px;">← Previous</a> ';
            }
            
            // Numbered links (smart pagination: show pages around current)
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1) {
                echo '<a href="' . htmlspecialchars($base . 'page=1') . '" style="margin-right: 5px;">1</a> ';
                if ($start_page > 2) {
                    echo '<span style="margin-right: 5px;">...</span> ';
                }
            }
            
            for ($p = $start_page; $p <= $end_page; $p++) {
                if ($p == $page) {
                    echo '<strong style="margin: 0 5px;">' . $p . '</strong> ';
                } else {
                    echo '<a href="' . htmlspecialchars($base . 'page=' . $p) . '" style="margin-right: 5px;">' . $p . '</a> ';
                }
            }
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span style="margin-right: 5px;">...</span> ';
                }
                echo '<a href="' . htmlspecialchars($base . 'page=' . $total_pages) . '" style="margin-right: 5px;">' . $total_pages . '</a> ';
            }
            
            // Next link
            if ($page < $total_pages) {
                echo '<a href="' . htmlspecialchars($base . 'page=' . ($page + 1)) . '" style="margin-right: 5px;">Next →</a>';
            }
            ?>
        </div>
        <p style="text-align: center; color: #666; font-size: 0.9em;">Page <?php echo $page; ?> of <?php echo $total_pages; ?> | Showing <?php echo count($orders); ?> of <?php echo $total_orders; ?> orders</p>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>