<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

include('../../connect.php');

$cartJson = $_POST['cart'] ?? '';
$paymentMethod = $_POST['payment_method'] ?? 'Cash';
$discountType = $_POST['discount_type'] ?? 'none';

$cart = json_decode($cartJson, true);
if (!is_array($cart) || empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty or invalid']);
    exit;
}

$validPayments = ['Cash', 'E-Wallet', 'Debit/Credit'];
if (!in_array($paymentMethod, $validPayments, true)) {
    $paymentMethod = 'Cash';
}

$cashGiven = null;
if ($paymentMethod === 'Cash') {
    $cashGiven = isset($_POST['cash_given']) ? floatval($_POST['cash_given']) : null;
    if ($cashGiven === null || $cashGiven <= 0) {
        echo json_encode(['success' => false, 'message' => 'Cash given must be entered for Cash payments.']);
        exit;
    }
}

$discountRate = 0;
if ($discountType === 'pwd' || $discountType === 'senior') {
    $discountRate = 0.05;
}

$subtotal = 0;
foreach ($cart as $item) {
    if (!isset($item['id'], $item['price'], $item['quantity'])) {
        continue;
    }
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}

$totalAmount = $subtotal - ($subtotal * $discountRate);
$totalAmount = round($totalAmount, 2);

if ($paymentMethod === 'Cash' && $cashGiven < $totalAmount) {
    echo json_encode(['success' => false, 'message' => 'Cash received is less than the total amount.']);
    exit;
}

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'User session is missing. Please log out and log in again.']);
    exit;
}

$userId = intval($_SESSION['id']);
$customerId = null;
$receiptNumber = 'R' . date('YmdHis') . rand(100, 999);

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO transactions (receipt_number, customer_id, user_id, total_amount, payment_method, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('sidds', $receiptNumber, $customerId, $userId, $totalAmount, $paymentMethod);
    if (!$stmt->execute()) {
        throw new Exception('Transaction insert failed: ' . $stmt->error);
    }
    $transactionId = $stmt->insert_id;
    $stmt->close();

    $itemStmt = $conn->prepare("INSERT INTO transaction_items (transaction_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    if (!$itemStmt) {
        throw new Exception('Prepare failed for item insert: ' . $conn->error);
    }

    $updateStockStmt = $conn->prepare("UPDATE products SET stock_quantity = GREATEST(stock_quantity - ?, 0) WHERE id = ?");
    if (!$updateStockStmt) {
        throw new Exception('Prepare failed for stock update: ' . $conn->error);
    }

    foreach ($cart as $item) {
        $productId = intval($item['id']);
        $quantity = intval($item['quantity']);
        $price = round(floatval($item['price']), 2);
        if ($quantity <= 0 || $productId <= 0) {
            continue;
        }

        $itemStmt->bind_param('iiid', $transactionId, $productId, $quantity, $price);
        if (!$itemStmt->execute()) {
            throw new Exception('Failed to save transaction item: ' . $itemStmt->error);
        }

        $updateStockStmt->bind_param('ii', $quantity, $productId);
        if (!$updateStockStmt->execute()) {
            throw new Exception('Failed to update stock: ' . $updateStockStmt->error);
        }
    }

    if ($itemStmt) {
        $itemStmt->close();
    }
    if ($updateStockStmt) {
        $updateStockStmt->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'receipt_number' => $receiptNumber]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Checkout failed: ' . $e->getMessage()]);
}
?>