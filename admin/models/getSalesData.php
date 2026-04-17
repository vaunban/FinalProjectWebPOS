<?php
session_start();
include(__DIR__ . "/../../config/connect.php");

if(!isset($_SESSION['username']) || $_SESSION['role'] != 'admin'){
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$cashier_id = isset($_GET['cashier_id']) ? $_GET['cashier_id'] : '';
$period = isset($_GET['period']) ? $_GET['period'] : 'daily';
$item_id = isset($_GET['item_id']) ? $_GET['item_id'] : '';

$where = [];
$params = [];
$types = '';

if ($from_date) {
    $where[] = "DATE(t.created_at) >= ?";
    $params[] = $from_date;
    $types .= 's';
}

if ($to_date) {
    $where[] = "DATE(t.created_at) <= ?";
    $params[] = $to_date;
    $types .= 's';
}

if ($cashier_id) {
    $where[] = "t.user_id = ?";
    $params[] = $cashier_id;
    $types .= 's';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : 'WHERE DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)';

// Adjust grouping based on period
switch ($period) {
    case 'weekly':
        $date_expr = "CONCAT(YEAR(t.created_at), '-W', LPAD(WEEK(t.created_at, 1), 2, '0')) AS report_date";
        $group_by = "YEAR(t.created_at), WEEK(t.created_at, 1)";
        $order_by = "YEAR(t.created_at) ASC, WEEK(t.created_at, 1) ASC";
        break;
    case 'monthly':
        $date_expr = "DATE_FORMAT(t.created_at, '%Y-%m') AS report_date";
        $group_by = "YEAR(t.created_at), MONTH(t.created_at)";
        $order_by = "YEAR(t.created_at) ASC, MONTH(t.created_at) ASC";
        break;
    default: // daily
        $date_expr = "DATE(t.created_at) AS report_date";
        $group_by = "DATE(t.created_at)";
        $order_by = "DATE(t.created_at) ASC";
        break;
}

$sales_sql = "SELECT $date_expr, COALESCE(SUM(t.total_amount), 0) AS sales_total, COUNT(*) AS transactions_count
              FROM transactions t
              $where_clause
              GROUP BY $group_by
              ORDER BY $order_by";

$stmt = $conn->prepare($sales_sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Query prepare failed']);
    exit();
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$report_data = [];
while ($row = $result->fetch_assoc()) {
    $report_data[] = [
        'date' => $row['report_date'],
        'sales_total' => (float)$row['sales_total'],
        'transactions_count' => (int)$row['transactions_count']
    ];
}
$stmt->close();

$summary_sql = "SELECT COALESCE(SUM(t.total_amount), 0) AS total_sales, COUNT(*) AS total_transactions, COALESCE(AVG(t.total_amount), 0) AS avg_amount
                FROM transactions t
                $where_clause";

$summary_stmt = $conn->prepare($summary_sql);
if ($summary_stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Query prepare failed']);
    exit();
}

if (!empty($params)) {
    $summary_stmt->bind_param($types, ...$params);
}
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();
$summary_stmt->close();

$payment_sql = "SELECT COALESCE(t.payment_method, 'Unknown') AS payment_method, COUNT(*) AS count, COALESCE(SUM(t.total_amount), 0) AS sales_total
                FROM transactions t
                $where_clause
                GROUP BY t.payment_method";
$payment_stmt = $conn->prepare($payment_sql);
if ($payment_stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Query prepare failed']);
    exit();
}

if (!empty($params)) {
    $payment_stmt->bind_param($types, ...$params);
}
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();
$payment_breakdown = [];
while ($row = $payment_result->fetch_assoc()) {
    $payment_breakdown[] = [
        'method' => $row['payment_method'],
        'count' => (int)$row['count'],
        'sales_total' => (float)$row['sales_total']
    ];
}
$payment_stmt->close();

$top_cashier_sql = "SELECT u.username AS cashier_name, COALESCE(SUM(t.total_amount), 0) AS total_sales
                    FROM transactions t
                    LEFT JOIN users u ON t.user_id = u.id
                    $where_clause
                    GROUP BY t.user_id
                    ORDER BY total_sales DESC
                    LIMIT 5";
$top_cashier_stmt = $conn->prepare($top_cashier_sql);
if ($top_cashier_stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Query prepare failed']);
    exit();
}

if (!empty($params)) {
    $top_cashier_stmt->bind_param($types, ...$params);
}
$top_cashier_stmt->execute();
$top_cashier_result = $top_cashier_stmt->get_result();
$top_cashiers = [];
while ($row = $top_cashier_result->fetch_assoc()) {
    $top_cashiers[] = [
        'cashier_name' => $row['cashier_name'] ?: 'Unknown',
        'total_sales' => (float)$row['total_sales']
    ];
}
$top_cashier_stmt->close();

// Item sales report if item_id is provided
$item_report_data = [];
$item_name = '';
if ($item_id) {
    $item_where = $where;
    $item_params = $params;
    $item_types = $types;
    $item_where[] = "ti.product_id = ?";
    $item_params[] = $item_id;
    $item_types .= 's';
    $item_where_clause = !empty($item_where) ? 'WHERE ' . implode(' AND ', $item_where) : 'WHERE DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)';

    $item_sql = "SELECT $date_expr, COALESCE(SUM(ti.quantity * ti.price), 0) AS sales_total
                 FROM transactions t
                 JOIN transaction_items ti ON t.id = ti.transaction_id
                 $item_where_clause
                 GROUP BY $group_by
                 ORDER BY $order_by";

    $item_stmt = $conn->prepare($item_sql);
    if ($item_stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Item query prepare failed']);
        exit();
    }

    if (!empty($item_params)) {
        $item_stmt->bind_param($item_types, ...$item_params);
    }
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();
    while ($row = $item_result->fetch_assoc()) {
        $item_report_data[] = [
            'date' => $row['report_date'],
            'sales_total' => (float)$row['sales_total']
        ];
    }
    $item_stmt->close();

    // Get item name
    $name_sql = "SELECT name FROM products WHERE id = ?";
    $name_stmt = $conn->prepare($name_sql);
    $name_stmt->bind_param('s', $item_id);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();
    if ($name_row = $name_result->fetch_assoc()) {
        $item_name = $name_row['name'];
    }
    $name_stmt->close();
}

// Top products by quantity
$top_product_sql = "SELECT p.name AS product_name, COALESCE(SUM(ti.quantity), 0) AS total_quantity
                    FROM transaction_items ti
                    JOIN transactions t ON ti.transaction_id = t.id
                    JOIN products p ON ti.product_id = p.id
                    $where_clause
                    GROUP BY ti.product_id
                    ORDER BY total_quantity DESC
                    LIMIT 5";
$top_product_stmt = $conn->prepare($top_product_sql);
if ($top_product_stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Top products query prepare failed']);
    exit();
}

if (!empty($params)) {
    $top_product_stmt->bind_param($types, ...$params);
}
$top_product_stmt->execute();
$top_product_result = $top_product_stmt->get_result();
$top_products = [];
while ($row = $top_product_result->fetch_assoc()) {
    $top_products[] = [
        'product_name' => $row['product_name'],
        'total_quantity' => (int)$row['total_quantity']
    ];
}
$top_product_stmt->close();

// Top categories by quantity
$top_category_sql = "SELECT COALESCE(c.name, 'Uncategorized') AS category_name, COALESCE(SUM(ti.quantity), 0) AS total_quantity
                     FROM transaction_items ti
                     JOIN transactions t ON ti.transaction_id = t.id
                     JOIN products p ON ti.product_id = p.id
                     LEFT JOIN categories c ON p.category_id = c.id
                     $where_clause
                     GROUP BY c.id, c.name
                     ORDER BY total_quantity DESC
                     LIMIT 5";
$top_category_stmt = $conn->prepare($top_category_sql);
if ($top_category_stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Top categories query prepare failed']);
    exit();
}

if (!empty($params)) {
    $top_category_stmt->bind_param($types, ...$params);
}
$top_category_stmt->execute();
$top_category_result = $top_category_stmt->get_result();
$top_categories = [];
while ($row = $top_category_result->fetch_assoc()) {
    $top_categories[] = [
        'category_name' => $row['category_name'],
        'total_quantity' => (int)$row['total_quantity']
    ];
}
$top_category_stmt->close();

$conn->close();

header('Content-Type: application/json');
echo json_encode([
    'report_data' => $report_data,
    'summary' => [
        'total_sales' => (float)$summary['total_sales'],
        'total_transactions' => (int)$summary['total_transactions'],
        'avg_amount' => (float)$summary['avg_amount']
    ],
    'payment_breakdown' => $payment_breakdown,
    'top_cashiers' => $top_cashiers,
    'top_products' => $top_products,
    'top_categories' => $top_categories
]);
