<?php
include (__DIR__ . '/../../../connect.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['ids']) || !is_array($_POST['ids']) || count($_POST['ids']) === 0) {
    header('Location: ../inventory.php');
    exit;
}

$ids = array_map('intval', $_POST['ids']);
$fields = [];

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
        echo 'Price must be at least 1.';
        echo '<br><a href="../inventory.php">Go Back</a>';
        exit;
    }
}

if (isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== '') {
    $fields['stock_quantity'] = intval($_POST['stock_quantity']);
    if ($fields['stock_quantity'] < 0) {
        echo 'Stock quantity cannot be negative.';
        echo '<br><a href="../inventory.php">Go Back</a>';
        exit;
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
        echo 'A different product with the same name already exists.';
        echo '<br><a href="../inventory.php">Go Back</a>';
        exit;
    }
}

$imageFilenames = [];
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
        echo 'Please upload a valid product image.';
        echo '<br><a href="../inventory.php">Go Back</a>';
        exit;
    }

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $imageFileType = mime_content_type($_FILES['product_image']['tmp_name']);
    if (!in_array($imageFileType, $allowedTypes)) {
        echo 'Only JPG, PNG, and GIF images are allowed.';
        echo '<br><a href="../inventory.php">Go Back</a>';
        exit;
    }

    $originalName = basename($_FILES['product_image']['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $targetDir = __DIR__ . '/../../../cashier/cashierassets/images/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filenameBase = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $baseName = $filenameBase . '_' . time();
    $firstFilename = $baseName . '_1.' . $extension;
    $firstPath = $targetDir . $firstFilename;

    if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $firstPath)) {
        echo 'Unable to upload image. Please try again.';
        echo '<br><a href="../inventory.php">Go Back</a>';
        exit;
    }

    $imageFilenames[] = $firstFilename;
    if (count($ids) > 1) {
        foreach (array_slice($ids, 1) as $index => $id) {
            $copyFilename = $baseName . '_' . ($index + 2) . '.' . $extension;
            $copyPath = $targetDir . $copyFilename;
            if (!copy($firstPath, $copyPath)) {
                echo 'Unable to prepare image for bulk update.';
                echo '<br><a href="../inventory.php">Go Back</a>';
                exit;
            }
            $imageFilenames[] = $copyFilename;
        }
    }

    $fields['icon_filename'] = true;
}

if (empty($fields)) {
    header('Location: ../inventory.php');
    exit;
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
        echo 'Error updating product ID ' . $id . ': ' . $conn->error;
        echo '<br><a href="../inventory.php">Go Back</a>';
        exit;
    }
}

header('Location: ../inventory.php');
exit;
