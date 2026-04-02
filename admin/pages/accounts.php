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
    <link rel="stylesheet" href="css/accountsstyle.css">
</head>
<body>
 
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><a href="../admin.php">MERKADO</a></h2>
            </div>
                <ul class="sidebar-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="transactions.php">Transactions</a></li>
                    <li><a href="accounts.php">Accounts</a></li>
                    <li><a href="assets/adminlogout.php">Log Out</a></li>
                </ul>
        </div>

        <main class="mainshift">
            <header class="mainshift-top">
                <div>
                    <div class="mainshift-top-title"><h1>Account Management</h1></div>

                    <form class="role-name-container" method = "GET">
                        <input class="searchbar" type="text" id="name" name="search" placeholder="Search by Name"> </input>
                    
                        <select  name = "role">
                            <option value = "" hidden selected > Filter: Role</option>
                            <option value = "admin"> Admin </option>
                            <option value = "cashier"> Cashier </option>
                        </select>
                    </form>
                </div> 
            </header>

            <table class="table-container" border = 1>
                <tr>
                    <th> USER ID </th>
                    <th> NAME </th>
                    <th> USERNAME </th>
                    <th> ROLE </th>
                    <th> STATUS </th>
                    <th> ACTIONS </th>
                </tr>

                <?php
                include (__DIR__ . '/../..//connect.php');

                $sql = "SELECT * FROM users";
                $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc())
                    {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td> name </td>";
                    echo "<td>" . $row['username'] . "</td>";
                    echo "<td>" . $row['role'] . "</td>";
                    echo "<td> status </td>";
                    echo"<td><a class='action-edit'>Edit</a>"."<a class='action-delete'>Delete</a></td>";
                    echo "</tr>";
                    }
                ?>
            </table>

            <button class="add-user-btn"> + Add User </button>

        </main>
</body>
</html>