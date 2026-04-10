<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: ../index.php");
    exit();
}

if($_SESSION['role'] != 'admin'){
    header("Location: ../cashier/cashier.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="pages/css/adminsstyle.css">
</head>
<body>
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="images/merkado-icon.png" alt="MERKADO logo">
                <h2><a href="admin.php">MERKADO</a></h2>
            </div>
                <ul class="sidebar-links">
                    <li><a href="pages/dashboard.php">Dashboard</a></li>
                    <li><a href="pages/inventory.php">Inventory</a></li>
                    <li><a href="pages/transactions.php">Transactions</a></li>
                    <li><a href="pages/accounts.php">Accounts</a></li>
                    <li><a href="pages/assets/adminlogout.php">Log Out</a></li>
                </ul>
        </div>

        <div class="mainshift">

        <div class="welcome-message">
             <h1>Welcome to<br>MERKADO,<br>ADMIN!</h1>
                <p>here's your overview</p>
            </div>
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

            <div class="low-stock-alerts">
                <h3>Low Stock Alerts</h3>
                <ul></ul>
            </div>

            <div class="chart-row">
                <div class="chart-card">
                    <h3>Sales Trend</h3>
                    <canvas id="salesLineChart" height="200"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Payment Breakdown</h3>
                    <canvas id="paymentPieChart" height="200"></canvas>
                </div>
            </div>
          
        </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const totalSalesEl = document.getElementById('totalSales');
            const totalTransactionsEl = document.getElementById('totalTransactions');
            const avgTransactionEl = document.getElementById('avgTransaction');
            const topCashierEl = document.getElementById('topCashier');
            let salesLineChart, paymentPieChart;

            function formatCurrency(value) {
                return '₱' + Number(value).toFixed(2);
            }

            function updateSummary(summary) {
                totalSalesEl.textContent = formatCurrency(summary.total_sales);
                totalTransactionsEl.textContent = summary.total_transactions;
                avgTransactionEl.textContent = formatCurrency(summary.avg_amount);
            }

            function renderTopCashier(cashiers) {
                topCashierEl.textContent = cashiers[0] ? cashiers[0].cashier_name : 'N/A';
            }

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

            function loadSummary() {
                fetch('pages/assets/getSalesData.php')
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

            function loadLowStock() {
                fetch('pages/assets/getLowStock.php')
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

            loadSummary();
            loadLowStock();
         
        });
    </script>
    <script src="../jquery-4.0.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="lowStockAlert.js"></script>
</body>
</html>