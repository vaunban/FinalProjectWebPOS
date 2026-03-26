<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: ../index.php");
    exit();
}

if($_SESSION['role'] != 'admin'){
    header("Location: ../../cashier/cashier.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/stockstyle.css">

</head>
<body>

    <div class="sidebar">
            <div class="sidebar-header">
                <h2><a href="../admin.php">MERKADO</a></h1>
            </div>
                <ul class="sidebar-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="customers.php">Customers</a></li>
                    <li><a href="accounts.php">Accounts</a></li>
                    <li><a href="assets/adminlogout.php">Log Out</a></li>
                </ul>
        </div>

        <div class="mainshift">
            <h1>Welcome to Inventory</h1>
            <p>what what what what what what</p>
        </div>
        <div>
           <?php
            include (__DIR__ . '/../..//connect.php');
            $sql = "SELECT p.name AS product_name,p.id,p.price,p.stock_quantity,c.name AS category_name
            FROM products p
            INNER JOIN categories c 
            ON p.id = c.id";
            $result = $conn->query($sql);
             if($result->num_rows >0) {
                echo "<table border = 1>";
                echo "<tr><th>ID</th>
                <th>Product Name</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Category</th>
                <th>Action</th>
                </tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['product_name'] . "</td>";
                    echo "<td>" . $row['price'] . "</td>";
                    echo "<td>" . $row['stock_quantity'] . "</td>";
                    echo "<td>" . $row['category_name'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "No stock data found.";
            }
            ?>
        </div>
        <div>
            <button id="openAddStock">Add Stock</button>
                <div id="addStockForm" class="AddStock-form">
                    <div class="AddStock-form-content">
                    <span class="close-btn">&times;</span>
                    <h2>Add Stocks</h2>
                    <form action="addStock.php" method="POST">
                        <input type="text" name="id" id="productId"><br>
                        <label for="stock_quantity">Stock Quantity:</label><br>
                        <input type="number" name="stock_quantity" id="stock_quantity" required><br>
                    <button id="saveAddStock">Save Changes</button>
                    </div>
</div>
        </div>
        <script src="script/stockscript.js"></script>
</body>
</html>