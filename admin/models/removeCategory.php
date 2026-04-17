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
    respond(false, 'No category selected for deletion.');
}

$successCount = 0;
$errors = [];

foreach ($ids as $id) {
    $select = $conn->prepare('SELECT id, name FROM categories WHERE id = ?');
    $select->bind_param('i', $id);
    $select->execute();
    $result = $select->get_result();

    if ($result->num_rows === 0) {
        $errors[] = "Category ID $id not found.";
        continue;
    }

    $row = $result->fetch_assoc();

    $conn->begin_transaction();

    // Archive the category row before deletion.
    $archive = $conn->prepare('INSERT INTO categories_archive (id, name, date) VALUES (?, ?, ?)');
    $archivedAt = date('Y-m-d H:i:s');
    $archive->bind_param('iss', $row['id'], $row['name'], $archivedAt);

    if (!$archive->execute()) {
        $conn->rollback();
        $errors[] = "Error archiving category ID $id: " . $conn->error;
        continue;
    }

    // Save the category ID in the archived_category_id column of products BEFORE deleting the category
    // This allows them to get it back when restored
    $updateProducts = $conn->prepare('UPDATE products SET archived_category_id = ? WHERE category_id = ?');
    $updateProducts->bind_param('ii', $id, $id);
    if (!$updateProducts->execute()) {
        $conn->rollback();
        $errors[] = "Error updating products for category ID $id: " . $conn->error;
        continue;
    }

    // Remove the category row (ON DELETE SET NULL will turn category_id into NULL for these products)
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
