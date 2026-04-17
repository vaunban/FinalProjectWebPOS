<?php
/**
 * checkout.php
 * Handles the checkout POST request from the cashier POS.
 * Validates the session, request method, cart contents, payment details,
 * and discount type. Then, within a database transaction, it:
 *   1. Creates a record in the 'transactions' table.
 *   2. Inserts each cart item into 'transaction_items'.
 *   3. Decrements stock in the 'products' table for each sold item.
 * Returns a JSON response indicating success (with receipt number) or failure.
 *
 * Called via: AJAX (POST) from posscript.js → #confirm-checkout click handler
 */

session_start();

// Set the response type to JSON for all responses
header('Content-Type: application/json');

// Reject the request if the user is not authenticated
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Include the database connection
include(__DIR__ . '/../../config/connect.php');

// Read and decode the cart JSON, payment method, discount type, and customer info from POST
$cartJson = $_POST['cart'] ?? '';
$paymentMethod = $_POST['payment_method'] ?? 'Cash';
$discountType = $_POST['discount_type'] ?? 'none';
$customerName = trim($_POST['customer_name'] ?? '');
$contactNumber = trim($_POST['contact_number'] ?? '');

// Decode the cart JSON into a PHP array and validate it
$cart = json_decode($cartJson, true);
if (!is_array($cart) || empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty or invalid']);
    exit;
}

// Sanitize the payment method — fall back to 'Cash' if an invalid value is submitted
$validPayments = ['Cash', 'E-Wallet', 'Debit/Credit'];
if (!in_array($paymentMethod, $validPayments, true)) {
    $paymentMethod = 'Cash';
}

// Validate cash given amount for Cash payments
$cashGiven = null;
if ($paymentMethod === 'Cash') {
    $cashGiven = isset($_POST['cash_given']) ? floatval($_POST['cash_given']) : null;
    if ($cashGiven === null || $cashGiven <= 0) {
        echo json_encode(['success' => false, 'message' => 'Cash given must be entered for Cash payments.']);
        exit;
    }
}

// Determine discount rate based on discount type (PWD and Senior Citizen get 5% off)
$discountRate = 0;
if ($discountType === 'pwd' || $discountType === 'senior') {
    $discountRate = 0.05;
}

// Calculate the subtotal by summing price × quantity for all valid cart items
$subtotal = 0;
foreach ($cart as $item) {
    if (!isset($item['id'], $item['price'], $item['quantity'])) {
        continue; // Skip malformed cart items
    }
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}

// Apply the discount and round to 2 decimal places
$totalAmount = $subtotal - ($subtotal * $discountRate);
$totalAmount = round($totalAmount, 2);

// Reject if cash given is less than the total amount due
if ($paymentMethod === 'Cash' && $cashGiven < $totalAmount) {
    echo json_encode(['success' => false, 'message' => 'Cash received is less than the total amount.']);
    exit;
}

// Ensure the user's session ID is available for logging the transaction
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'User session is missing. Please log out and log in again.']);
    exit;
}

// Retrieve the logged-in user's ID from the session
$userId = intval($_SESSION['id']);

// Insert the customer record if a name was provided; otherwise treat as a guest (null)
$customerId = null;
if ($customerName !== '') {
    $custStmt = $conn->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
    if ($custStmt) {
        $custStmt->bind_param('ss', $customerName, $contactNumber);
        if ($custStmt->execute()) {
            // Use the newly created customer's ID to link this transaction
            $customerId = $custStmt->insert_id;
        }
        $custStmt->close();
    }
}

// Generate a unique receipt number using the current datetime and a random suffix
$receiptNumber = 'R' . date('YmdHis') . rand(100, 999);

// Begin a database transaction to ensure all inserts and updates are atomic
$conn->begin_transaction();
try {
    // --- Step 1: Insert the main transaction record ---
    $stmt = $conn->prepare("INSERT INTO transactions (receipt_number, customer_id, user_id, total_amount, payment_method, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('sidds', $receiptNumber, $customerId, $userId, $totalAmount, $paymentMethod);
    if (!$stmt->execute()) {
        throw new Exception('Transaction insert failed: ' . $stmt->error);
    }

    // Get the newly created transaction ID for linking items
    $transactionId = $stmt->insert_id;
    $stmt->close();

    // --- Step 2: Prepare statement for inserting individual transaction items ---
    $itemStmt = $conn->prepare("INSERT INTO transaction_items (transaction_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    if (!$itemStmt) {
        throw new Exception('Prepare failed for item insert: ' . $conn->error);
    }

    // --- Step 3: Prepare statement for decrementing product stock ---
    // GREATEST(..., 0) prevents stock from going below zero
    $updateStockStmt = $conn->prepare("UPDATE products SET stock_quantity = GREATEST(stock_quantity - ?, 0) WHERE id = ?");
    if (!$updateStockStmt) {
        throw new Exception('Prepare failed for stock update: ' . $conn->error);
    }

    // Loop through each cart item and save it, then reduce stock
    foreach ($cart as $item) {
        $productId = intval($item['id']);
        $quantity = intval($item['quantity']);
        // Store the unit price at the time of sale
        $price = round(floatval($item['price']), 2);

        // Skip items with invalid quantity or product ID
        if ($quantity <= 0 || $productId <= 0) {
            continue;
        }

        // Insert this item into transaction_items
        $itemStmt->bind_param('iiid', $transactionId, $productId, $quantity, $price);
        if (!$itemStmt->execute()) {
            throw new Exception('Failed to save transaction item: ' . $itemStmt->error);
        }

        // Deduct sold quantity from the product's stock
        $updateStockStmt->bind_param('ii', $quantity, $productId);
        if (!$updateStockStmt->execute()) {
            throw new Exception('Failed to update stock: ' . $updateStockStmt->error);
        }
    }

    // Close both prepared statements after the loop
    if ($itemStmt) {
        $itemStmt->close();
    }
    if ($updateStockStmt) {
        $updateStockStmt->close();
    }

    // Commit the transaction — all changes are saved permanently
    $conn->commit();

    // Return success response with the receipt number
    echo json_encode(['success' => true, 'receipt_number' => $receiptNumber]);

} catch (Exception $e) {
    // Roll back all changes if any step failed, and return an error response
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Checkout failed: ' . $e->getMessage()]);
}
?>