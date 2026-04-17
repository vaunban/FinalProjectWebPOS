<?php
/**
 * gettransaction.php
 * Returns paginated transaction data as JSON for the admin transactions page.
 * Supports filtering by date range, cashier, and payment method.
 * Returns: transaction list, pagination info, and summary totals.
 * Called via AJAX from transactionscript.js.
 */

session_start();
include(__DIR__ . "/../../config/connect.php");

// Check authentication
if(!isset($_SESSION['username']) || $_SESSION['role'] != 'admin'){
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Read filter parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$cashier_id = isset($_GET['cashier_id']) ? $_GET['cashier_id'] : '';
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Number of transactions per page
$offset = ($page - 1) * $limit;

// Build dynamic WHERE clause based on active filters
$where = [];
$params = [];

if($from_date){
    $where[] = "DATE(t.created_at) >= ?";
    $params[] = $from_date;
}
if($to_date){
    $where[] = "DATE(t.created_at) <= ?";
    $params[] = $to_date;
}
if($cashier_id){
    $where[] = "t.user_id = ?";
    $params[] = $cashier_id;
}
if($payment_method){
    $where[] = "t.payment_method = ?";
    $params[] = $payment_method;
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Query 1: Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM transactions t $where_clause";
$count_stmt = $conn->prepare($count_sql);
if(!empty($params)){
    $types = str_repeat("s", count($params));
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Query 2: Get the transactions for the current page
$sql = "SELECT t.*, u.username as cashier_name, c.name as customer_name 
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN customers c ON t.customer_id = c.id
        $where_clause
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$types = str_repeat("s", count($params) - 2) . "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Build the transactions array
$transactions = [];
while($row = $result->fetch_assoc()){
    $transactions[] = $row;
}

// Query 3: Get summary totals (total count, total sales, average)
$summary_sql = "SELECT COUNT(*) as total_count, COALESCE(SUM(total_amount), 0) as total_sales, COALESCE(AVG(total_amount), 0) as avg_amount 
                FROM transactions t $where_clause";
$summary_stmt = $conn->prepare($summary_sql);
if(!empty($params)){
    // Exclude the LIMIT and OFFSET params for the summary query
    $summary_params = array_slice($params, 0, -2);
    if(!empty($summary_params)){
        $summary_stmt->bind_param(str_repeat("s", count($summary_params)), ...$summary_params);
    }
}
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();

// Return everything as JSON
echo json_encode([
    'transactions' => $transactions,
    'total_pages' => $total_pages,
    'current_page' => $page,
    'summary' => [
        'total_count' => $summary['total_count'] ?? 0,
        'total_sales' => $summary['total_sales'] ?? 0,
        'avg_amount' => $summary['avg_amount'] ?? 0
    ]
]);

$conn->close();
?>