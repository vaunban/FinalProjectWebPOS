<?php
/**
 * addCategory.php
 * Handles adding a new product category to the database.
 * Supports both AJAX requests (returns JSON) and regular form submissions (redirects).
 * Called from the inventory page's Add Category modal.
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

// Get the category name from the form
$name = trim($_POST['name'] ?? '');

// Validate the category name
if (empty($name)) {
    respond(false, 'Category name is required.');
}

// Insert the new category into the database
$insert = $conn->prepare('INSERT INTO categories (name) VALUES (?)');
$insert->bind_param('s', $name);

if ($insert->execute()) {
    respond(true, 'Category added successfully.');
} else {
    respond(false, 'Error adding category: ' . $conn->error);
}
