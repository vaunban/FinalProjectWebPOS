<?php
include (__DIR__ . '/../../../connect.php');

// Helper to return either JSON for AJAX requests or redirect for normal form submission.
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

// Ensure this script only handles POST form submissions.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// Read the required product fields from the submitted form.
$name = trim($_POST['name'] ?? '');
$price = intval($_POST['price'] ?? 0);
$stock_quantity = intval($_POST['stock_quantity'] ?? 0);
$category_id = intval($_POST['category_id'] ?? 0);

if ($name === '') {
    respond(false, 'Product name is required.');
}

$checkSql = $conn->prepare('SELECT name FROM products WHERE name = ?');
$checkSql->bind_param('s', $name);
$checkSql->execute();
$checkResult = $checkSql->get_result();
if ($checkResult->num_rows === 1) {
    respond(false, "Product $name already exists.");
}

if ($stock_quantity < 1) {
    respond(false, 'At least 1 stock must be added.');
}

if ($price < 1) {
    respond(false, 'Price must be at least 1.');
}

if (!isset($_FILES['product_image'])) {
    respond(false, 'No product image was uploaded.');
}

// Handle common PHP file upload errors explicitly.
$uploadError = $_FILES['product_image']['error'];
if ($uploadError !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive.',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive.',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
    ];

    $message = $uploadErrors[$uploadError] ?? 'Unknown upload error.';
    respond(false, $message);
}

$imageTmpPath = $_FILES['product_image']['tmp_name'];
if (!is_uploaded_file($imageTmpPath)) {
    respond(false, 'Uploaded file is not valid.');
}

// Validate the uploaded file MIME type using the best available method.
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$imageFileType = '';

if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $imageFileType = finfo_file($finfo, $imageTmpPath);
    finfo_close($finfo);
}

if (!$imageFileType && function_exists('mime_content_type')) {
    $imageFileType = mime_content_type($imageTmpPath);
}

if (!$imageFileType && function_exists('getimagesize')) {
    $imageInfo = getimagesize($imageTmpPath);
    if ($imageInfo) {
        $imageFileType = $imageInfo['mime'];
    }
}

if (!in_array($imageFileType, $allowedTypes, true)) {
    respond(false, 'Only JPG, PNG, GIF, and WEBP images are allowed. Detected: ' . $imageFileType);
}

// Validate the original file extension as a second layer of protection.
$originalName = basename($_FILES['product_image']['name']);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
    respond(false, 'Only JPG, PNG, GIF, and WEBP files are allowed.');
}

$targetDir = __DIR__ . '/../../../cashier/cashierassets/images/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
$targetFile = $targetDir . $filename . '_' . time() . '.' . $extension;
if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFile)) {
    respond(false, 'Unable to upload image. Please try again.');
}

$icon_filename = basename($targetFile);
$sql = $conn->prepare('INSERT INTO products (name, price, stock_quantity, category_id, prodStatus, icon_filename) VALUES (?, ?, ?, ?, "Inactive", ?)');
$sql->bind_param('siiis', $name, $price, $stock_quantity, $category_id, $icon_filename);
if ($sql->execute()) {
    respond(true, 'Product added successfully.');
} else {
    respond(false, 'Error adding product: ' . $conn->error);
}
?>