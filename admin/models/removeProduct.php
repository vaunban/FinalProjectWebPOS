<?php
/**
 * removeProduct.php
 * Archives (soft-deletes) one or more products.
 * Copies each product to products_archive (including image filename),
 * then deletes the product from the active products table.
 * Uses database transactions to ensure data consistency.
 * Called via AJAX from stockscript.js.
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

// Get product ID(s) — supports single and bulk delete
$ids = [];
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
} elseif (isset($_POST['id'])) {
    $ids = [intval($_POST['id'])];
}

$ids = array_filter($ids, fn($id) => $id > 0);
if (count($ids) === 0) {
    respond(false, 'No product selected for deletion.');
}

$successCount = 0;
$errors = [];

foreach ($ids as $id) {
    // Fetch the product data (including image filename)
    $select = $conn->prepare('SELECT id, name, price, stock_quantity, category_id, prodStatus, icon_filename FROM products WHERE id = ?');
    $select->bind_param('i', $id);
    $select->execute();
    $result = $select->get_result();

    if ($result->num_rows === 0) {
        $errors[] = "Product ID $id not found.";
        continue;
    }

    $row = $result->fetch_assoc();

    // Step 1: Save the product to the archive table (preserves the image filename)
    $archive = $conn->prepare('INSERT INTO products_archive (id, name, price, stock_quantity, category_id, prodStatus, icon_filename, archived_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $archivedAt = date('Y-m-d H:i:s');
    $archive->bind_param('ississss', $row['id'], $row['name'], $row['price'], $row['stock_quantity'], $row['category_id'], $row['prodStatus'], $row['icon_filename'], $archivedAt);

    // Start a transaction for atomicity
    $conn->begin_transaction();

    if (!$archive->execute()) {
        $conn->rollback();
        $errors[] = "Error archiving product ID $id: " . $conn->error;
        continue;
    }

    // Step 2: Delete the product from the active products table
    $delete = $conn->prepare('DELETE FROM products WHERE id = ?');
    $delete->bind_param('i', $id);
    if ($delete->execute()) {
        $conn->commit();
        $successCount++;
    } else {
        $conn->rollback();
        $errors[] = "Error deleting product ID $id: " . $conn->error;
    }
}

// Return the result
if ($successCount > 0 && empty($errors)) {
    respond(true, 'Product(s) deleted successfully.');
}

$message = 'Deletion completed with some issues.';
if ($successCount === 0) {
    $message = 'No products were deleted.';
}
if (!empty($errors)) {
    $message .= ' ' . implode(' ', $errors);
}
respond($successCount > 0, $message);

