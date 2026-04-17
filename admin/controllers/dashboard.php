<?php
/**
 * dashboard.php
 * Admin dashboard page for the MERKADO POS system.
 * Shows sales reports with date/cashier filters, sales trend charts,
 * payment breakdown, top products, top categories, and top cashiers.
 * Only accessible to users with the 'admin' role.
 */

// Start session and verify login
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit();
}

// Redirect to cashier page if user is not an admin
if ($_SESSION['role'] != 'admin') {
    header("Location: ../../cashier/controllers/cashier.php");
    exit();
}

// Fetch all cashiers for the filter dropdown
include(__DIR__ . "/../../config/connect.php");
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
    <!-- Dashboard styles -->
    <link rel="stylesheet" href="../views/css/dashstyle.css">
</head>

<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../images/merkado-icon.png" alt="MERKADO logo">
            <h2><a href="admin.php">MERKADO</a></h2>
        </div>
        <ul class="sidebar-links">
            <li><a class="active" href="dashboard.php">Dashboard</a></li>
            <li><a href="inventory.php">Inventory</a></li>
            <li><a href="transactions.php">Transactions</a></li>
            <li><a href="accounts.php">Accounts</a></li>
            <li><a href="../models/adminlogout.php">Log Out</a></li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="mainshift">
        <!-- Page Header -->
        <div class="header-section">
            <h1>Dashboard</h1>
            <p>Visualize your recent sales performance and payment trends.</p>
        </div>

        <!-- Filter Panel — date range, cashier, and refresh button -->
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
                    <!-- Dynamically render cashier options from database -->
                    <?php foreach ($cashiers as $cashier): ?>
                        <option value="<?= htmlspecialchars($cashier['id']) ?>">
                        
                                <?= htmlspecialchars($cashier['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item filter-action">
                <button id="refreshReport">Refresh Report</button>
            </div>
        </div>

        <!-- Summary Cards — total sales, transactions, average, top cashier -->
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

        <!-- Charts Row 1: Sales Trend + Payment Breakdown -->
        <div class="chart-row">
            <!-- Sales trend line chart with period selector (daily/weekly/monthly) -->
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
            <!-- Payment method breakdown pie chart -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <h2>Payment Breakdown</h2>
                    <p>Revenue by payment method.</p>
                </div>
                <canvas id="paymentPieChart" height="240"></canvas>
            </div>
        </div>

        <!-- Charts Row 2: Top Products + Top Categories -->
        <div class="chart-row">
            <!-- Top selling products bar chart -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <h2>Top Products</h2>
                    <p>Best-selling products by quantity.</p>
                </div>
                <canvas id="topProductsChart" height="240"></canvas>
            </div>
            <!-- Top selling categories bar chart -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <h2>Top Categories</h2>
                    <p>Highest-selling categories by quantity.</p>
                </div>
                <canvas id="topCategoriesChart" height="240"></canvas>
            </div>
        </div>

        <!-- Top Cashiers List -->
        <div class="list-card">
            <div class="chart-card-header">
                <h2>Top Cashiers by Sales</h2>
            </div>
            <ul id="topCashiersList" class="top-cashiers-list"></ul>
        </div>
    </div>

    <!-- External Scripts -->
    <script src="../../public/js/jquery-4.0.0.min.js"></script>
    <script src="../views/js/chart.js"></script>
    <script src="../views/js/dashboardscript.js"></script>
    <script src="../views/js/lowStockAlert.js"></script>
</body>

</html>