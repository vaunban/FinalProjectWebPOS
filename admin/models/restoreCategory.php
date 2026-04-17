<?php
include(__DIR__ . '/../../config/connect.php');

function respond($success, $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

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

    // Insert the category back into the active categories table
    $restore = $conn->prepare('INSERT INTO categories (id, name) VALUES (?, ?)');
    $restore->bind_param('is', $originalCatId, $catName);

    $newCatId = $originalCatId;

    if (!$restore->execute()) {
        // If there's an ID clash, insert without ID and let AUTO_INCREMENT assign a new one
        $restoreNew = $conn->prepare('INSERT INTO categories (name) VALUES (?)');
        $restoreNew->bind_param('s', $catName);
        if (!$restoreNew->execute()) {
            $conn->rollback();
            $errors[] = "Error restoring category: " . $conn->error;
            continue;
        }
        $newCatId = $conn->insert_id;
    }
    
    // Restore products' category_id ONLY if they haven't been given a new one manually
    $updateProducts = $conn->prepare('UPDATE products SET category_id = ? WHERE archived_category_id = ? AND (category_id IS NULL OR category_id = 0)');
    $updateProducts->bind_param('ii', $newCatId, $originalCatId);
    if (!$updateProducts->execute()) {
        $conn->rollback();
        $errors[] = "Error restoring categories to products: " . $conn->error;
        continue;
    }

    // Clean up the archived_category_id column for any product that had it
    $cleanProducts = $conn->prepare('UPDATE products SET archived_category_id = NULL WHERE archived_category_id = ?');
    $cleanProducts->bind_param('i', $originalCatId);
    $cleanProducts->execute();

    // Delete from archive
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
