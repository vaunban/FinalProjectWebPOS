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

include("../../connect.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - MERKADO Admin</title>
    <link rel="stylesheet" href="css/transactionstyle.css">
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

    <div class="mainshift">
        <div class="header-section">
            <h1>Transactions</h1>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-row">
                <div class="filter-group">
                    <label>From Date:</label>
                    <input type="date" id="dateFrom" class="filter-input">
                </div>
                <div class="filter-group">
                    <label>To Date:</label>
                    <input type="date" id="dateTo" class="filter-input">
                </div>
                <div class="filter-group">
                    <label>Cashier:</label>
                    <select id="filterCashier" class="filter-input">
                        <option value="">All Cashiers</option>
                        <?php
                        $cashier_sql = "SELECT id, username FROM users WHERE role = 'cashier'";
                        $cashier_result = $conn->query($cashier_sql);
                        while($cashier = $cashier_result->fetch_assoc()){
                            echo "<option value='{$cashier['id']}'>{$cashier['username']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Payment Method:</label>
                    <select id="filterPayment" class="filter-input">
                        <option value="">All</option>
                        <option value="Cash">Cash</option>
                        <option value="E-Wallet">E-Wallet</option>
                        <option value="Debit/Credit">Debit/Credit</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button id="searchBtn" class="search-btn">Search</button>
                    <button id="resetBtn" class="reset-btn">Reset</button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <h3>Total Transactions</h3>
                <p id="totalTransactions">0</p>
            </div>
            <div class="summary-card">
                <h3>Total Sales</h3>
                <p id="totalSales">₱0.00</p>
            </div>
            <div class="summary-card">
                <h3>Average Transaction</h3>
                <p id="avgTransaction">₱0.00</p>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="table-container">
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Cashier</th>
                        <th>Customer</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Date & Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="transactionsBody">
                    <tr><td colspan="7">Loading...</td><tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>
    </div>

    <!-- View Transaction Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Transaction Details</h2>
                <span class="close">&times;</span>
            </div>
            <div id="modalBody"></div>
        </div>
    </div>

    <script src="../../jquery-4.0.0.min.js"></script>
    <script src="script/transactionscript.js"></script>
</body>
</html>