<?php
include (__DIR__ . '/../../../connect.php');

function respond($success, $message, $redirect = '../inventory.php') {
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
    respond(false, 'No product selected for deletion.');
}

$successCount = 0;
$errors = [];

foreach ($ids as $id) {
    $select = $conn->prepare('SELECT id, name, price, stock_quantity, category_id, prodStatus, icon_filename FROM products WHERE id = ?');
    $select->bind_param('i', $id);
    $select->execute();
    $result = $select->get_result();

    if ($result->num_rows === 0) {
        $errors[] = "Product ID $id not found.";
        continue;
    }

    $row = $result->fetch_assoc();

    // Archive the product row before deletion so it can be restored later.
    // The icon_filename is preserved so the image remains available after restore.
    $archive = $conn->prepare('INSERT INTO products_archive (id, name, price, stock_quantity, category_id, prodStatus, icon_filename, archived_at, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $archivedAt = date('Y-m-d H:i:s');
    $reason = 'Deleted by admin';
    $archive->bind_param('ississsss', $row['id'], $row['name'], $row['price'], $row['stock_quantity'], $row['category_id'], $row['prodStatus'], $row['icon_filename'], $archivedAt, $reason);

    $conn->begin_transaction();

    if (!$archive->execute()) {
        $conn->rollback();
        $errors[] = "Error archiving product ID $id: " . $conn->error;
        continue;
    }

    // Remove the product row from the active products table after successful archive.
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

