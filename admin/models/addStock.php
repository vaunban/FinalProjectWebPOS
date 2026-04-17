<?php
/**
 * addStock.php
 * Handles adding stock quantity to an existing product.
 * Increments the stock_quantity of a product by the specified amount.
 * Supports both AJAX (JSON response) and regular form submissions (redirect).
 * Called from the inventory page's Add Stock modal.
 */

include(__DIR__ . '/../../config/connect.php');

// Helper function: returns JSON for AJAX or redirects for normal form submissions
function respond($success, $message, $redirect = '../controllers/inventory.php') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }

    if ($success) {
        header("Location: $redirect");
        exit;
    }

    echo $message;
    echo "<br><a href=\"$redirect\">Go Back</a>";
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// Get the product ID and quantity from the form
$id = intval($_POST['id'] ?? 0);
$stock_quantity = intval($_POST['stock_quantity'] ?? 0);

// Check if the product exists
$checkSql = $conn->prepare('SELECT id FROM products WHERE id = ?');
$checkSql->bind_param('i', $id);
$checkSql->execute();
$checkResult = $checkSql->get_result();
if ($checkResult->num_rows === 0) {
    respond(false, "Product ID $id does not exist.");
}

// Validate the quantity
if ($stock_quantity < 1) {
    respond(false, 'At least 1 stock must be added.');
}

// Add the stock quantity to the existing amount
$sql = $conn->prepare('UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?');
$sql->bind_param('ii', $stock_quantity, $id);
if ($sql->execute()) {
    respond(true, 'Stock added successfully.');
} else {
    respond(false, 'Error adding stock: ' . $conn->error);
}
?>
