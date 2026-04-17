<?php
include(__DIR__ . '/../../config/connect.php');

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

$name = trim($_POST['name'] ?? '');

if (empty($name)) {
    respond(false, 'Category name is required.');
}

$insert = $conn->prepare('INSERT INTO categories (name) VALUES (?)');
$insert->bind_param('s', $name);

if ($insert->execute()) {
    respond(true, 'Category added successfully.');
} else {
    respond(false, 'Error adding category: ' . $conn->error);
}
