<?php
include (__DIR__ . '/../..//connect.php');
$id = $_POST['id'];
$stock_quantity = $_POST['stock_quantity'];
$sql = "UPDATE products SET stock_quantity = stock_quantity + '$stock_quantity' WHERE id = '$id'";
if ($conn->query($sql) === TRUE) {
    if($stock_quantity < 1) {
       echo "Please add atleast 1 stock.";
    } else {
    echo header("Location: /admin/pages/inventory.php");
    }
} else {
    echo "Error updating stock: " . $conn->error;
    echo "<a href='/admin/pages/inventory.php'>Go back to Inventory</a>";
}
?>