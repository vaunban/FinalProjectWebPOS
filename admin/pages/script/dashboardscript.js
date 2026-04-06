document.addEventListener('DOMContentLoaded', function() {
    const fromDateInput = document.getElementById('reportFrom');
    const toDateInput = document.getElementById('reportTo');
    const cashierSelect = document.getElementById('filterCashier');
    const refreshBtn = document.getElementById('refreshReport');
    const trendPeriodSelect = document.getElementById('trendPeriod');
    const totalSalesEl = document.getElementById('totalSales');
    const totalTransactionsEl = document.getElementById('totalTransactions');
    const avgTransactionEl = document.getElementById('avgTransaction');
    const topCashierEl = document.getElementById('topCashier');
    const lineCanvas = document.getElementById('salesLineChart');
    const pieCanvas = document.getElementById('paymentPieChart');
    const topProductsCanvas = document.getElementById('topProductsChart');
    const topCategoriesCanvas = document.getElementById('topCategoriesChart');
    const topCashiersList = document.getElementById('topCashiersList');

    let salesLineChart;
    let paymentPieChart;
    let topProductsChart;
    let topCategoriesChart;

    function formatCurrency(value) {
        return '₱' + Number(value).toFixed(2);
    }

    function createLineChart(labels, values, period) {
        if (salesLineChart) {
            salesLineChart.destroy();
        }

        const periodLabel = period.charAt(0).toUpperCase() + period.slice(1) + ' Sales';

        salesLineChart = new Chart(lineCanvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: periodLabel,
                    data: values,
                    fill: true,
                    borderColor: '#3d5f3a',
                    backgroundColor: 'rgba(92, 138, 79, 0.18)',
                    tension: 0.25,
                    pointRadius: 4,
                    pointBackgroundColor: '#3d5f3a'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return formatCurrency(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value;
                            }
                        }
                    }
                }
            }
        });
    }

    function createPieChart(labels, values) {
        if (paymentPieChart) {
            paymentPieChart.destroy();
        }

        const colors = ['#5c8a4f', '#f7b733', '#4f7aac', '#9c4f8a', '#6a6a6a'];

        paymentPieChart = new Chart(pieCanvas, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.slice(0, labels.length),
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    function createTopProductsChart(labels, values) {
        if (topProductsChart) {
            topProductsChart.destroy();
        }

        topProductsChart = new Chart(topProductsCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Units Sold',
                    data: values,
                    backgroundColor: '#f7b733',
                    borderColor: '#d69e00',
                    borderWidth: 1,
                    borderRadius: 8,
                    maxBarThickness: 48
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' units';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    function createTopCategoriesChart(labels, values) {
        if (topCategoriesChart) {
            topCategoriesChart.destroy();
        }

        topCategoriesChart = new Chart(topCategoriesCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Units Sold',
                    data: values,
                    backgroundColor: '#4f7aac',
                    borderColor: '#375a80',
                    borderWidth: 1,
                    borderRadius: 8,
                    maxBarThickness: 48
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' units';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    function updateSummary(summary) {
        totalSalesEl.textContent = formatCurrency(summary.total_sales);
        totalTransactionsEl.textContent = summary.total_transactions;
        avgTransactionEl.textContent = formatCurrency(summary.avg_amount);
    }

    function renderTopCashiers(cashiers) {
        if (!cashiers.length) {
            topCashiersList.innerHTML = '<li>No cashier sales found for this range.</li>';
            return;
        }

        const html = cashiers.map(function(cashier) {
            return `<li><span>${cashier.cashier_name}</span><strong>${formatCurrency(cashier.total_sales)}</strong></li>`;
        }).join('');

        topCashiersList.innerHTML = html;
        topCashierEl.textContent = cashiers[0] ? cashiers[0].cashier_name : 'N/A';
    }

    function loadSalesReport() {
        const params = new URLSearchParams();
        if (fromDateInput.value) params.append('from_date', fromDateInput.value);
        if (toDateInput.value) params.append('to_date', toDateInput.value);
        if (cashierSelect.value) params.append('cashier_id', cashierSelect.value);
        params.append('period', trendPeriodSelect.value);

        fetch('assets/getSalesData.php?' + params.toString())
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Failed to load report data');
                }
                return response.json();
            })
            .then(function(data) {
                updateSummary(data.summary);
                renderTopCashiers(data.top_cashiers);

                const labels = data.report_data.map(function(row) {
                    return row.date;
                });
                const values = data.report_data.map(function(row) {
                    return row.sales_total;
                });

                createLineChart(labels, values, trendPeriodSelect.value);

                const pieLabels = data.payment_breakdown.map(function(item) {
                    return item.method;
                });
                const pieValues = data.payment_breakdown.map(function(item) {
                    return item.sales_total;
                });

                createPieChart(pieLabels, pieValues);

                const productLabels = data.top_products.map(function(item) {
                    return item.product_name;
                });
                const productValues = data.top_products.map(function(item) {
                    return item.total_quantity;
                });
                createTopProductsChart(productLabels, productValues);

                const categoryLabels = data.top_categories.map(function(item) {
                    return item.category_name;
                });
                const categoryValues = data.top_categories.map(function(item) {
                    return item.total_quantity;
                });
                createTopCategoriesChart(categoryLabels, categoryValues);
            })
            .catch(function(error) {
                console.error(error);
                alert('Unable to load sales report. Please try again.');
            });
    }

    refreshBtn.addEventListener('click', function() {
        loadSalesReport();
    });
    trendPeriodSelect.addEventListener('change', function() {
        loadSalesReport();
    });
    loadSalesReport();
});