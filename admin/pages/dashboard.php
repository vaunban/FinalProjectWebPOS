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
$cashiers = [];
$cashier_query = "SELECT id, username FROM users WHERE role = 'cashier' ORDER BY username ASC";
$cashier_result = $conn->query($cashier_query);
if ($cashier_result) {
    while ($cashier = $cashier_result->fetch_assoc()) {
        $cashiers[] = $cashier;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MERKADO Admin</title>
    <link rel="stylesheet" href="css/dashboardsstyle.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><a href="../admin.php">MERKADO</a></h2>
        </div>
        <ul class="sidebar-links">
            <li><a class="active" href="dashboard.php">Dashboard</a></li>
            <li><a href="inventory.php">Inventory</a></li>
            <li><a href="transactions.php">Transactions</a></li>
            <li><a href="accounts.php">Accounts</a></li>
            <li><a href="assets/adminlogout.php">Log Out</a></li>
        </ul>
    </div>

    <div class="mainshift">
        <div class="header-section">
            <h1>Dashboard</h1>
            <p>Visualize your recent sales performance and payment trends.</p>
        </div>

        <div class="filter-panel">
            <div class="filter-item">
                <label for="reportFrom">From</label>
                <input type="date" id="reportFrom">
            </div>
            <div class="filter-item">
                <label for="reportTo">To</label>
                <input type="date" id="reportTo">
            </div>
            <div class="filter-item">
                <label for="filterCashier">Cashier</label>
                <select id="filterCashier">
                    <option value="">All Cashiers</option>
                    <?php foreach ($cashiers as $cashier): ?>
                        <option value="<?= htmlspecialchars($cashier['id']) ?>"><?= htmlspecialchars($cashier['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item filter-action">
                <button id="refreshReport">Refresh Report</button>
            </div>
        </div>

        <div class="report-row">
            <div class="report-card">
                <h3>Total Sales</h3>
                <p id="totalSales">₱0.00</p>
            </div>
            <div class="report-card">
                <h3>Total Transactions</h3>
                <p id="totalTransactions">0</p>
            </div>
            <div class="report-card">
                <h3>Average Transaction</h3>
                <p id="avgTransaction">₱0.00</p>
            </div>
            <div class="report-card">
                <h3>Top Cashier</h3>
                <p id="topCashier">N/A</p>
            </div>
        </div>

        <div class="chart-row">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h2>Sales Trend</h2>
                    <p>Daily sales totals for the selected date range.</p>
                    <div class="chart-controls">
                        <label for="trendPeriod">Period:</label>
                        <select id="trendPeriod">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                </div>
                <canvas id="salesLineChart" height="240"></canvas>
            </div>
            <div class="chart-card">
                <div class="chart-card-header">
                    <h2>Payment Breakdown</h2>
                    <p>Revenue by payment method.</p>
                </div>
                <canvas id="paymentPieChart" height="240"></canvas>
            </div>
        </div>

        <div class="chart-row">
            <div class="chart-card">
                <div class="chart-card-header">
                    <h2>Top Products</h2>
                    <p>Best-selling products by quantity.</p>
                </div>
                <canvas id="topProductsChart" height="240"></canvas>
            </div>
            <div class="chart-card">
                <div class="chart-card-header">
                    <h2>Top Categories</h2>
                    <p>Highest-selling categories by quantity.</p>
                </div>
                <canvas id="topCategoriesChart" height="240"></canvas>
            </div>
        </div>

        <div class="list-card">
            <div class="chart-card-header">
                <h2>Top Cashiers by Sales</h2>
            </div>
            <ul id="topCashiersList" class="top-cashiers-list"></ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="script/dashboardscript.js"></script>
</body>
</html>
