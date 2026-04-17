<?php
/**
 * removeCategory.php
 * Archives (soft-deletes) one or more categories.
 * For each category: saves it to categories_archive, updates products to remember
 * their original category_id, then deletes the category.
 * Uses database transactions to ensure data consistency.
 * Called via AJAX from stockscript.js.
 */

include(__DIR__ . '/../../config/connect.php');

// Helper function: returns JSON response
function respond($success, $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// Get category ID(s) — supports single and bulk delete
$ids = [];
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
} elseif (isset($_POST['id'])) {
    $ids = [intval($_POST['id'])];
}

$ids = array_filter($ids, fn($id) => $id > 0);
if (count($ids) === 0) {
    respond(false, 'No category selected for deletion.');
}

$successCount = 0;
$errors = [];

foreach ($ids as $id) {
    // Fetch the category data
    $select = $conn->prepare('SELECT id, name FROM categories WHERE id = ?');
    $select->bind_param('i', $id);
    $select->execute();
    $result = $select->get_result();

    if ($result->num_rows === 0) {
        $errors[] = "Category ID $id not found.";
        continue;
    }

    $row = $result->fetch_assoc();

    // Start a transaction for atomicity
    $conn->begin_transaction();

    // Step 1: Save the category to the archive table
    $archive = $conn->prepare('INSERT INTO categories_archive (id, name, date) VALUES (?, ?, ?)');
    $archivedAt = date('Y-m-d H:i:s');
    $archive->bind_param('iss', $row['id'], $row['name'], $archivedAt);

    if (!$archive->execute()) {
        $conn->rollback();
        $errors[] = "Error archiving category ID $id: " . $conn->error;
        continue;
    }

    // Step 2: Save the category_id in archived_category_id so products can be restored later
    $updateProducts = $conn->prepare('UPDATE products SET archived_category_id = ? WHERE category_id = ?');
    $updateProducts->bind_param('ii', $id, $id);
    if (!$updateProducts->execute()) {
        $conn->rollback();
        $errors[] = "Error updating products for category ID $id: " . $conn->error;
        continue;
    }

    // Step 3: Delete the category (ON DELETE SET NULL will clear category_id on products)
    $delete = $conn->prepare('DELETE FROM categories WHERE id = ?');
    $delete->bind_param('i', $id);
    if ($delete->execute()) {
        $conn->commit();
        $successCount++;
    } else {
        $conn->rollback();
        $errors[] = "Error deleting category ID $id: " . $conn->error;
    }
}

// Return the result
if ($successCount > 0 && empty($errors)) {
    respond(true, 'Category(s) deleted and archived successfully.');
}

$message = 'Deletion completed with some issues.';
if ($successCount === 0) {
    $message = 'No categories were deleted.';
}
if (!empty($errors)) {
    $message .= ' ' . implode(' ', $errors);
}
respond($successCount > 0, $message);
