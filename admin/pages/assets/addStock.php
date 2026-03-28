<?php
include (__DIR__ . '../../../../connect.php');
$id = $_POST['id'];
$stock_quantity = $_POST['stock_quantity'];
$checkSql = $conn->prepare("SELECT id FROM products WHERE id = ?");
$checkSql->bind_param("i", $id);
$checkSql->execute();
$checkResult = $checkSql->get_result();
if ($checkResult->num_rows === 0) {
    echo "Product ID $id does not exist.";
    echo "<a href='../inventory.php'>Go Back</a>";
    exit();
}
if ($stock_quantity < 1) {
    echo "At least 1 stock must be added.";
    echo "<a href='../inventory.php'>Go Back</a>";
    exit;
}
$sql = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
$sql->bind_param("ii", $stock_quantity, $id);
if ($sql->execute()) {
    echo "Stock added successfully.";
    header("Location: ../inventory.php");
} else {
    echo "Error adding stock: " . $conn->error;
    echo "<a href='../inventory.php'>Go Back</a>";
}
?>
