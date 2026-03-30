<?php
include (__DIR__ . '/../../../connect.php');

function respond($success, $message, $redirect = '../inventory.php') {
    // Return either JSON for AJAX or a normal redirect for form submissions.
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

// Ensure we have at least one product ID for the update.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['ids']) || !is_array($_POST['ids']) || count($_POST['ids']) === 0) {
    respond(false, 'No product selected for update.');
}

$ids = array_map('intval', $_POST['ids']);
$fields = [];

// Helper to bind dynamic mysqli parameters safely.
function bindParams($stmt, $types, &$params) {
    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

if (isset($_POST['name']) && trim($_POST['name']) !== '') {
    $fields['name'] = trim($_POST['name']);
}

if (isset($_POST['price']) && $_POST['price'] !== '') {
    $fields['price'] = intval($_POST['price']);
    if ($fields['price'] < 1) {
        respond(false, 'Price must be at least 1.');
    }
}

if (isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== '') {
    $fields['stock_quantity'] = intval($_POST['stock_quantity']);
    if ($fields['stock_quantity'] < 0) {
        respond(false, 'Stock quantity cannot be negative.');
    }
}

if (isset($_POST['category_id']) && $_POST['category_id'] !== '') {
    $fields['category_id'] = intval($_POST['category_id']);
}

if (isset($_POST['prodStatus']) && $_POST['prodStatus'] !== '') {
    $fields['prodStatus'] = trim($_POST['prodStatus']);
}

if (isset($fields['name'])) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $checkSql = $conn->prepare('SELECT id FROM products WHERE name = ? AND id NOT IN (' . $placeholders . ')');
    $params = array_merge([$fields['name']], $ids);
    bindParams($checkSql, 's' . str_repeat('i', count($ids)), $params);
    $checkSql->execute();
    $checkResult = $checkSql->get_result();
    if ($checkResult->num_rows > 0) {
        respond(false, 'A different product with the same name already exists.');
    }
}

// If a new product image is provided, validate it and save it to the images folder.
$imageFilenames = [];
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
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
        respond(false, $uploadErrors[$uploadError] ?? 'Please upload a valid product image.');
    }

    $imageTmpPath = $_FILES['product_image']['tmp_name'];
    if (!is_uploaded_file($imageTmpPath)) {
        respond(false, 'Uploaded file is not valid.');
    }

    // Detect MIME type using multiple methods for compatibility.
    $allowedTypes = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 'image/webp'];
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

    $originalName = basename($_FILES['product_image']['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        respond(false, 'Only JPG, PNG, GIF, and WEBP file extensions are allowed.');
    }

    // Save the uploaded image to the product images directory.
    $targetDir = __DIR__ . '/../../../cashier/cashierassets/images/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filenameBase = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $baseName = $filenameBase . '_' . time();
    $firstFilename = $baseName . '_1.' . $extension;
    $firstPath = $targetDir . $firstFilename;

    if (!move_uploaded_file($imageTmpPath, $firstPath)) {
        respond(false, 'Unable to upload image. Please try again.');
    }

    $imageFilenames[] = $firstFilename;
    if (count($ids) > 1) {
        foreach (array_slice($ids, 1) as $index => $id) {
            $copyFilename = $baseName . '_' . ($index + 2) . '.' . $extension;
            $copyPath = $targetDir . $copyFilename;
            if (!copy($firstPath, $copyPath)) {
                respond(false, 'Unable to prepare image for bulk update.');
            }
            $imageFilenames[] = $copyFilename;
        }
    }

    $fields['icon_filename'] = true;
}

if (empty($fields)) {
    respond(false, 'No update fields were submitted.');
}

foreach ($ids as $index => $id) {
    $updateFields = [];
    $updateValues = [];
    $updateTypes = '';

    if (isset($fields['name'])) {
        $updateFields[] = 'name = ?';
        $updateValues[] = $fields['name'];
        $updateTypes .= 's';
    }
    if (isset($fields['price'])) {
        $updateFields[] = 'price = ?';
        $updateValues[] = $fields['price'];
        $updateTypes .= 'i';
    }
    if (isset($fields['stock_quantity'])) {
        $updateFields[] = 'stock_quantity = ?';
        $updateValues[] = $fields['stock_quantity'];
        $updateTypes .= 'i';
    }
    if (isset($fields['category_id'])) {
        $updateFields[] = 'category_id = ?';
        $updateValues[] = $fields['category_id'];
        $updateTypes .= 'i';
    }
    if (isset($fields['prodStatus'])) {
        $updateFields[] = 'prodStatus = ?';
        $updateValues[] = $fields['prodStatus'];
        $updateTypes .= 's';
    }
    if (isset($fields['icon_filename'])) {
        $updateFields[] = 'icon_filename = ?';
        $updateValues[] = $imageFilenames[$index] ?? $imageFilenames[0];
        $updateTypes .= 's';

        $select = $conn->prepare('SELECT icon_filename FROM products WHERE id = ?');
        $select->bind_param('i', $id);
        $select->execute();
        $result = $select->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['icon_filename'])) {
                $oldImagePath = __DIR__ . '/../../../cashier/cashierassets/images/' . basename($row['icon_filename']);
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }
        }
    }

    if (empty($updateFields)) {
        continue;
    }

    $updateValues[] = $id;
    $updateTypes .= 'i';
    $sql = 'UPDATE products SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
    $stmt = $conn->prepare($sql);
    bindParams($stmt, $updateTypes, $updateValues);
    if (!$stmt->execute()) {
        respond(false, 'Error updating product ID ' . $id . ': ' . $conn->error);
    }
}

respond(true, 'Product update completed successfully.');
