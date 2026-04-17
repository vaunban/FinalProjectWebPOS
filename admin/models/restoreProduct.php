<?php
/**
 * restoreProduct.php
 * Restores one or more archived products back to the active products table.
 * Checks for ID conflicts before restoring, then removes the archive record.
 * Uses database transactions for atomicity.
 * Called via AJAX from stockscript.js.
 */

include __DIR__ . '/../../config/connect.php';

header('Content-Type: application/json');

// Helper function: returns JSON response
function respond($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// Get archive ID(s) to restore
$ids = [];
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
} elseif (isset($_POST['id'])) {
    $ids = [intval($_POST['id'])];
}

$ids = array_filter($ids, fn($id) => $id > 0);
if (count($ids) === 0) {
    respond(false, 'No archive item selected for restore.');
}

$successCount = 0;
$errors = [];

foreach ($ids as $id) {
    // Fetch the archived product data
    $select = $conn->prepare('SELECT id, name, price, stock_quantity, category_id, prodStatus, icon_filename FROM products_archive WHERE archived_id = ?');
    $select->bind_param('i', $id);
    $select->execute();
    $result = $select->get_result();

    if ($result->num_rows === 0) {
        $errors[] = "Archive ID $id not found.";
        continue;
    }

    $row = $result->fetch_assoc();
    $productId = $row['id'];

    // Check if a product with the same ID already exists (prevent conflicts)
    $check = $conn->prepare('SELECT id FROM products WHERE id = ?');
    $check->bind_param('i', $productId);
    $check->execute();
    $existing = $check->get_result();
    if ($existing && $existing->num_rows > 0) {
        $errors[] = "Product ID $productId already exists in inventory.";
        continue;
    }

    // Start a transaction for atomicity
    $conn->begin_transaction();

    // Step 1: Re-insert the product into the active products table
    $insert = $conn->prepare('INSERT INTO products (id, name, price, stock_quantity, category_id, prodStatus, icon_filename) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $insert->bind_param('ississs', $row['id'], $row['name'], $row['price'], $row['stock_quantity'], $row['category_id'], $row['prodStatus'], $row['icon_filename']);

    if (!$insert->execute()) {
        $conn->rollback();
        $errors[] = "Error restoring archive ID $id: " . $conn->error;
        continue;
    }

    // Step 2: Remove the record from the archive table
    $delete = $conn->prepare('DELETE FROM products_archive WHERE archived_id = ?');
    $delete->bind_param('i', $id);
    if (!$delete->execute()) {
        $conn->rollback();
        $errors[] = "Error deleting archive ID $id after restore: " . $conn->error;
        continue;
    }

    $conn->commit();
    $successCount++;
}

// Return the result
if ($successCount > 0 && empty($errors)) {
    respond(true, 'Product restored successfully.');
}

$message = 'Restore completed with some issues.';
if ($successCount === 0) {
    $message = 'No products were restored.';
}
if (!empty($errors)) {
    $message .= ' ' . implode(' ', $errors);
}
respond($successCount > 0, $message);
