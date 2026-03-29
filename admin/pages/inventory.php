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
    <link rel="stylesheet" href="./css/stockstyle.css">

</head>
<body>

    <div class="sidebar">
            <div class="sidebar-header">
                <h2><a href="../admin.php">MERKADO</a></h2>
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
            $sql = "SELECT p.name AS product_name,p.id,p.price,p.stock_quantity,p.prodStatus,c.name AS category_name
            FROM products p
            LEFT JOIN categories c 
            ON p.category_id = c.id";
            $result = $conn->query($sql);
             if($result->num_rows >0) {
                echo "<table border = 1>";
                echo "<tr><th>ID</th>
                <th>Product Name</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Category</th>
                <th>Status</th>
                <th>Action</th>
                </tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['product_name'] . "</td>";
                    echo "<td>" . $row['price'] . "</td>";
                    echo "<td>" . $row['stock_quantity'] . "</td>";
                    echo "<td>" . $row['prodStatus'] . "</td>";
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
            
            <button type="button" id="openAddStock">Add Stock</button>
                <div id="addStockForm" class="AddStock-form">
                    <div class="AddStock-form-content">
                    <span class="close-btn">&times;</span>
                    <h2>Add Stocks</h2>
                    <form action="../pages/assets/addStock.php" method="POST">
                        <input type="text" name="id" id="productId"><br>
                        <label for="stock_quantity">Stock Quantity:</label><br>
                        <input type="number" name="stock_quantity" id="stock_quantity" required><br>
                        
                        <button id="saveAddStock" type="submit">Save Changes</button>
                    </form>
                    </div>
                    
                </div>
        </div>
         <div>
            <button type="button" id="openAddProd">Add Product</button>
                <div id="addProdForm" class="AddProd-form">
                    <div class="AddProd-form-content">
                    <span class="close-btn">&times;</span>
                    <h2>Add Products</h2>
                    <form action="../pages/assets/addProduct.php" method="POST">
                        <input type="hidden" name="id" id="id"><br>
                        <label for="name">Product Name:</label><br>
                        <input type="text" name="name" id="name" required placeholder = "name"><br>
                        <label for="price">Price:</label><br>
                        <input type="number" name="price" id="price" required placeholder = "Price"><br>
                        <label for="stock_quantity">Stock Quantity:</label><br>
                        <input type="number" name="stock_quantity" id="stock_quantity" required placeholder = "Stock Quantity"><br>
                        <label for="category_id">Category:</label><br>
                        <input type="number" name="category_id" id="category_id" required placeholder = "Category ID"><br>
                        <button id="saveAddProd" type="submit">Save Changes</button><br>
                        <div class="category-list">
                            <?php
                             include (__DIR__ . '/../..//connect.php');
                             $sql = "SELECT id, name FROM categories";
                             $result = $conn->query($sql);
                             if ($result->num_rows > 0) {
                                echo "<table border=1 class=\"centered-table\">";
                                echo "<tr><th>Category ID</th><th>Category Name</th></tr>";
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr><td>" . $row['id'] . "</td><td>" . $row['name'] . "</td></tr>";
                                }
                                echo "</table>";
                             } else {
                                echo "<p>No categories found.</p>";
                             }
                            ?>
                        </div>
                    </form>
                    </div>
                </div>
        </div>
        
        <script src="./script/stockscript.js"></script>
</body>
</html>