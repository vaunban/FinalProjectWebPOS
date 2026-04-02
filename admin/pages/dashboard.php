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
    <link rel="stylesheet" href="css/dashboardstyle.css">
</head>
<body>

    <div class="sidebar">
            <div class="sidebar-header">
                <h2><a href="../admin.php">MERKADO</a></h1>
            </div>
                <ul class="sidebar-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="transactions.php">Transactions</a></li>
                    <li><a href="accounts.php">Accounts</a></li>
                    <li><a href="assets/adminlogout.php">Log Out</a></li>
                </ul>
        </div>

        <div class="mainshift">
            <h1>Welcome to Dashboard</h1>
            <p>what what what what what what</p>
        </div>
</body>
</html>