<?php
include (__DIR__ . '/../../../connect.php');
$id = $_POST['id'];
$stock_quantity = $_POST['stock_quantity'];
$name = $_POST['name'];
$price = $_POST['price'];
$category_id = $_POST['category_id'];
$checkSql = $conn->prepare("SELECT name FROM products WHERE name = ?");
$checkSql->bind_param("s", $name);
$checkSql->execute();
$checkResult = $checkSql->get_result();
if ($checkResult->num_rows === 1) {
    echo "Product $name already exist.";
    echo "<a href='../inventory.php'>Go Back</a>";
    exit();
}
if ($stock_quantity < 1) {
    echo "At least 1 stock must be added.";
    echo "<a href='../inventory.php'>Go Back</a>";
    exit;
}
if($price < 1) {
    echo "Price must be at least 1.";
    echo "<a href='../inventory.php'>Go Back</a>";
    exit;
}
if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
    echo "Please upload a valid product image.";
    echo "<a href='../inventory.php'>Go Back</a>";
    exit;
}
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$imageFileType = mime_content_type($_FILES['product_image']['tmp_name']);
if (!in_array($imageFileType, $allowedTypes)) {
    echo "Only JPG, PNG, and GIF images are allowed.";
    echo "<a href='../inventory.php'>Go Back</a>";
    exit;
}
$originalName = basename($_FILES['product_image']['name']);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$targetDir = __DIR__ . '/../../../cashier/cashierassets/images/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}
$filename = pathinfo($originalName, PATHINFO_FILENAME);
$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $filename);
$targetFile = $targetDir . $filename . '_' . time() . '.' . $extension;
if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFile)) {
    echo "Unable to upload image. Please try again.";
    echo "<a href='../inventory.php'>Go Back</a>";
    exit;
}
$icon_filename = basename($targetFile);
$sql = $conn->prepare("INSERT INTO products (name, price, stock_quantity, category_id, prodStatus, icon_filename) VALUES (?, ?, ?, ?, 'Inactive', ?)");
$sql->bind_param("siiis", $name, $price, $stock_quantity, $category_id, $icon_filename);
if ($sql->execute()) {
    echo "Product added successfully.";
    header("Location: ../inventory.php");
} else {
    echo "Error adding product: " . $conn->error;
    echo "<a href='../inventory.php'>Go Back</a>";
}
?>