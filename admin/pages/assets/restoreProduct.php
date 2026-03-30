<?php
include __DIR__ . '/../../../connect.php';

header('Content-Type: application/json');

function respond($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Only allow POST requests for restoring products.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// Extract archive IDs from the request body safely.
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

    // Prevent restoring a product if an active product with the same ID already exists.
    $check = $conn->prepare('SELECT id FROM products WHERE id = ?');
    $check->bind_param('i', $productId);
    $check->execute();
    $existing = $check->get_result();
    if ($existing && $existing->num_rows > 0) {
        $errors[] = "Product ID $productId already exists in inventory.";
        continue;
    }

    // Use a transaction so insert and archive delete are atomic and the product is either fully restored or not changed.
    $conn->begin_transaction();

    // Restore the archived product row back to the active products table, including the saved image filename.
    $insert = $conn->prepare('INSERT INTO products (id, name, price, stock_quantity, category_id, prodStatus, icon_filename) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $insert->bind_param('ississs', $row['id'], $row['name'], $row['price'], $row['stock_quantity'], $row['category_id'], $row['prodStatus'], $row['icon_filename']);

    if (!$insert->execute()) {
        $conn->rollback();
        $errors[] = "Error restoring archive ID $id: " . $conn->error;
        continue;
    }

    // Remove the restored entry from the archive after the product is recreated.
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
