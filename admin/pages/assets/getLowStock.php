<?php
session_start();
include("../../../connect.php");

if(!isset($_SESSION['username']) || $_SESSION['role'] != 'admin'){
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$low_stock_sql = "SELECT p.name AS product_name, p.stock_quantity AS current_stock
                  FROM products p
                  WHERE p.stock_quantity < 20 AND p.stock_quantity > 0
                  ORDER BY p.stock_quantity ASC
                  LIMIT 10";

$stmt = $conn->prepare($low_stock_sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Query prepare failed']);
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

$low_stock_items = [];
while ($row = $result->fetch_assoc()) {
    $low_stock_items[] = [
        'product_name' => $row['product_name'],
        'current_stock' => (int)$row['current_stock']
    ];
}
$stmt->close();

$conn->close();

header('Content-Type: application/json');
echo json_encode($low_stock_items);
?>