<?php
/**
 * admin.php
 * Admin landing/home page for the MERKADO POS system.
 * Shows a welcome message, summary cards (total sales, transactions, etc.),
 * low stock alerts, and sales charts.
 * Only accessible to users with the 'admin' role.
 */

// Start session and check if user is logged in
session_start();

// Redirect to login if not logged in
if(!isset($_SESSION['username'])){
    header("Location: ../../index.php");
    exit();
}

// Redirect to cashier page if user is not an admin
if($_SESSION['role'] != 'admin'){
    header("Location: ../../cashier/controllers/cashier.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- Admin home page styles -->
    <link rel="stylesheet" href="../views/css/adminsstyle.css">
</head>
<body>
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/merkado-icon.png" alt="MERKADO logo">
                <h2><a href="admin.php">MERKADO</a></h2>
            </div>
                <ul class="sidebar-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="transactions.php">Transactions</a></li>
                    <li><a href="accounts.php">Accounts</a></li>
                    <li><a href="../models/adminlogout.php">Log Out</a></li>
                </ul>
        </div>

        <!-- Main Content Area -->
        <div class="mainshift">

        <!-- Welcome Section -->
        <div class="welcome-message">
             <h1>Welcome to<br>MERKADO,<br>ADMIN!</h1>
                <p>here's your overview</p>
            </div>

            <!-- Summary Cards — populated by JavaScript below -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Total Sales</h3>
                    <p id="totalSales">₱0.00</p>
                </div>
                <div class="summary-card">
                    <h3>Total Transactions</h3>
                    <p id="totalTransactions">0</p>
                </div>
                <div class="summary-card">
                    <h3>Average Transaction</h3>
                    <p id="avgTransaction">₱0.00</p>
                </div>
                <div class="summary-card">
                    <h3>Top Cashier</h3>
                    <p id="topCashier">N/A</p>
                </div>
            </div>

            <!-- Low Stock Alerts List -->
            <div class="low-stock-alerts">
                <h3>Low Stock Alerts</h3>
                <ul></ul>
            </div>

            <!-- Sales Charts -->
            <div class="chart-row">
                <!-- Sales trend line chart -->
                <div class="chart-card">
                    <h3>Sales Trend</h3>
                    <canvas id="salesLineChart" height="200"></canvas>
                </div>
                <!-- Payment method pie chart -->
                <div class="chart-card">
                    <h3>Payment Breakdown</h3>
                    <canvas id="paymentPieChart" height="200"></canvas>
                </div>
            </div>
          
        </div>

    <!-- Inline JavaScript for the admin home page -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get references to the summary card elements
            const totalSalesEl = document.getElementById('totalSales');
            const totalTransactionsEl = document.getElementById('totalTransactions');
            const avgTransactionEl = document.getElementById('avgTransaction');
            const topCashierEl = document.getElementById('topCashier');
            let salesLineChart, paymentPieChart;

            // Format a number as Philippine Peso currency
            function formatCurrency(value) {
                return '₱' + Number(value).toFixed(2);
            }

            // Update the summary cards with data from the server
            function updateSummary(summary) {
                totalSalesEl.textContent = formatCurrency(summary.total_sales);
                totalTransactionsEl.textContent = summary.total_transactions;
                avgTransactionEl.textContent = formatCurrency(summary.avg_amount);
            }

            // Show the top cashier's name in the summary card
            function renderTopCashier(cashiers) {
                topCashierEl.textContent = cashiers[0] ? cashiers[0].cashier_name : 'N/A';
            }

            // Render the sales trend line chart using Chart.js
            function renderSalesTrend(reportData) {
                const ctx = document.getElementById('salesLineChart').getContext('2d');
                const labels = reportData.map(item => item.date);
                const data = reportData.map(item => item.sales_total);
                if (salesLineChart) salesLineChart.destroy();
                salesLineChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Sales',
                            data: data,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Render the payment method breakdown pie chart
            function renderPaymentBreakdown(paymentData) {
                const ctx = document.getElementById('paymentPieChart').getContext('2d');
                const labels = paymentData.map(item => item.method);
                const data = paymentData.map(item => item.sales_total);
                if (paymentPieChart) paymentPieChart.destroy();
                paymentPieChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(255, 205, 86, 0.2)',
                                'rgba(75, 192, 192, 0.2)',
                                'rgba(153, 102, 255, 0.2)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 205, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }

            // Fetch sales summary data from the server and update all cards and charts
            function loadSummary() {
                fetch('../models/getSalesData.php')
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Failed to load summary data');
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        updateSummary(data.summary);
                        renderTopCashier(data.top_cashiers);
                        renderSalesTrend(data.report_data);
                        renderPaymentBreakdown(data.payment_breakdown);
                    })
                    .catch(function(error) {
                        console.error(error);
                        alert('Unable to load summary. Please try again.');
                    });
            }

            // Fetch low stock products and display them in the alerts list
            function loadLowStock() {
                fetch('../models/getLowStock.php')
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Failed to load low stock data');
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        const lowStockContainer = document.querySelector('.low-stock-alerts ul');
                        if (data.length === 0) {
                            lowStockContainer.innerHTML = '<li>No low stock alerts at this time.</li>';
                        } else {
                            lowStockContainer.innerHTML = data.map(function(item) {
                                return `<li><span>${item.product_name}</span><strong>${item.current_stock} left</strong></li>`;
                            }).join('');
                        }
                    })
                    .catch(function(error) {
                        console.error(error);
                        const lowStockContainer = document.querySelector('.low-stock-alerts ul');
                        lowStockContainer.innerHTML = '<li>Unable to load low stock data.</li>';
                    });
            }

            // Load data when the page finishes loading
            loadSummary();
            loadLowStock();
         
        });
    </script>
    <!-- External scripts -->
    <script src="../../public/js/jquery-4.0.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../views/js/lowStockAlert.js"></script>
</body>
</html>