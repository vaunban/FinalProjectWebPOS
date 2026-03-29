<?php
include (__DIR__ . '../../../../connect.php');
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
$sql = $conn->prepare("INSERT INTO products (name, price, stock_quantity, category_id, prodStatus) VALUES (?, ?, ?, ?, 'Inactive')");
$sql->bind_param("siii", $name, $price, $stock_quantity, $category_id);
if ($sql->execute()) {
    echo "Product added successfully.";
    header("Location: ../inventory.php");
} else {
    echo "Error adding product: " . $conn->error;
    echo "<a href='../inventory.php'>Go Back</a>";
}
?>