<?php
session_start();
include("../../../connect.php");

if(!isset($_SESSION['username']) || $_SESSION['role'] != 'admin'){
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$transaction_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(!$transaction_id){
    echo json_encode(['error' => 'Invalid transaction ID']);
    exit();
}

// Get transaction header
$sql = "SELECT t.*, u.username as cashier_name, c.name as customer_name 
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN customers c ON t.customer_id = c.id
        WHERE t.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

if(!$transaction){
    echo json_encode(['error' => 'Transaction not found']);
    exit();
}

// Get transaction items
$items_sql = "SELECT ti.*, p.name as product_name 
              FROM transaction_items ti
              JOIN products p ON ti.product_id = p.id
              WHERE ti.transaction_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $transaction_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'transaction' => $transaction,
    'items' => $items
]);

$conn->close();
?>