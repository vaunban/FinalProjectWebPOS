<?php
/**
 * restoreCategory.php
 * Restores one or more archived categories back to the active categories table.
 * Also restores the category_id on any products that were affected when the category was deleted.
 * Uses database transactions for atomicity.
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

// Get archive ID(s) to restore
$ids = [];
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
} elseif (isset($_POST['id'])) {
    $ids = [intval($_POST['id'])];
}

$ids = array_filter($ids, fn($id) => $id > 0);
if (count($ids) === 0) {
    respond(false, 'No category selected for restore.');
}

$successCount = 0;
$errors = [];

foreach ($ids as $archivedId) {
    // Fetch the archived category data
    $select = $conn->prepare('SELECT id, name FROM categories_archive WHERE archived_id = ?');
    $select->bind_param('i', $archivedId);
    $select->execute();
    $result = $select->get_result();

    if ($result->num_rows === 0) {
        $errors[] = "Archived category ID $archivedId not found.";
        continue;
    }

    $row = $result->fetch_assoc();
    $originalCatId = $row['id'];
    $catName = $row['name'];

    $conn->begin_transaction();

    // Step 1: Re-insert the category with its original ID
    $restore = $conn->prepare('INSERT INTO categories (id, name) VALUES (?, ?)');
    $restore->bind_param('is', $originalCatId, $catName);

    $newCatId = $originalCatId;

    if (!$restore->execute()) {
        // If the ID is already taken, let AUTO_INCREMENT assign a new one
        $restoreNew = $conn->prepare('INSERT INTO categories (name) VALUES (?)');
        $restoreNew->bind_param('s', $catName);
        if (!$restoreNew->execute()) {
            $conn->rollback();
            $errors[] = "Error restoring category: " . $conn->error;
            continue;
        }
        $newCatId = $conn->insert_id;
    }
    
    // Step 2: Restore category_id on products that had this category before deletion
    // Only update products that haven't been manually reassigned to another category
    $updateProducts = $conn->prepare('UPDATE products SET category_id = ? WHERE archived_category_id = ? AND (category_id IS NULL OR category_id = 0)');
    $updateProducts->bind_param('ii', $newCatId, $originalCatId);
    if (!$updateProducts->execute()) {
        $conn->rollback();
        $errors[] = "Error restoring categories to products: " . $conn->error;
        continue;
    }

    // Step 3: Clean up the archived_category_id column
    $cleanProducts = $conn->prepare('UPDATE products SET archived_category_id = NULL WHERE archived_category_id = ?');
    $cleanProducts->bind_param('i', $originalCatId);
    $cleanProducts->execute();

    // Step 4: Remove the record from the archive
    $deleteArchive = $conn->prepare('DELETE FROM categories_archive WHERE archived_id = ?');
    $deleteArchive->bind_param('i', $archivedId);
    
    if ($deleteArchive->execute()) {
        $conn->commit();
        $successCount++;
    } else {
        $conn->rollback();
        $errors[] = "Error removing from category archive: " . $conn->error;
    }
}

// Return the result
if ($successCount > 0 && empty($errors)) {
    respond(true, 'Category(s) restored successfully.');
}

$message = 'Restore completed with some issues.';
if ($successCount === 0) {
    $message = 'No categories were restored.';
}
if (!empty($errors)) {
    $message .= ' ' . implode(' ', $errors);
}
respond($successCount > 0, $message);
