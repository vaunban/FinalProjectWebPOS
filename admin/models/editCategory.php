<?php
/**
 * editCategory.php
 * Handles updating one or more category names.
 * Supports both single edit (from the Edit button) and bulk edit (from selection mode).
 * Supports AJAX (JSON response) and regular form submissions (redirect).
 */

include(__DIR__ . '/../../config/connect.php');

// Helper function: returns JSON for AJAX or redirects for normal form submissions
function respond($success, $message) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }

    $redirect = '../controllers/inventory.php';
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

// Get category ID(s) — supports both single and bulk operations
$ids = [];
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
} elseif (isset($_POST['id'])) {
    $ids = [intval($_POST['id'])];
}

// Filter out invalid IDs
$ids = array_filter($ids, fn($id) => $id > 0);
if (count($ids) === 0) {
    respond(false, 'No category selected for editing.');
}

// Get the new category name
$name = trim($_POST['name'] ?? '');

if (empty($name)) {
    respond(false, 'Category name cannot be empty.');
}

// Update each selected category
$successCount = 0;
$errors = [];

foreach ($ids as $id) {
    $update = $conn->prepare('UPDATE categories SET name = ? WHERE id = ?');
    $update->bind_param('si', $name, $id);
    
    if ($update->execute()) {
        $successCount++;
    } else {
        $errors[] = "Error updating category ID $id: " . $conn->error;
    }
}

// Return the result
if ($successCount > 0 && empty($errors)) {
    respond(true, 'Category updated successfully.');
}

$message = 'Update completed with some issues.';
if ($successCount === 0) {
    $message = 'No categories were updated.';
}
if (!empty($errors)) {
    $message .= ' ' . implode(' ', $errors);
}
respond($successCount > 0, $message);
