$(function () {
    const $fromDateInput = $('#reportFrom');
    const $toDateInput = $('#reportTo');
    const $cashierSelect = $('#filterCashier');
    const $refreshBtn = $('#refreshReport');
    const $trendPeriodSelect = $('#trendPeriod');
    const $totalSalesEl = $('#totalSales');
    const $totalTransactionsEl = $('#totalTransactions');
    const $avgTransactionEl = $('#avgTransaction');
    const $topCashierEl = $('#topCashier');
    const lineCanvas = $('#salesLineChart')[0];
    const pieCanvas = $('#paymentPieChart')[0];
    const topProductsCanvas = $('#topProductsChart')[0];
    const topCategoriesCanvas = $('#topCategoriesChart')[0];
    const $topCashiersList = $('#topCashiersList');

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
                            label: function (context) {
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
                            callback: function (value) {
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
                            label: function (context) {
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
                            label: function (context) {
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
        $totalSalesEl.text(formatCurrency(summary.total_sales));
        $totalTransactionsEl.text(summary.total_transactions);
        $avgTransactionEl.text(formatCurrency(summary.avg_amount));
    }

    function renderTopCashiers(cashiers) {
        if (!cashiers.length) {
            $topCashiersList.html('<li>No cashier sales found for this range.</li>');
            return;
        }

        const html = cashiers.map(function (cashier) {
            return `<li><span>${cashier.cashier_name}</span><strong>${formatCurrency(cashier.total_sales)}</strong></li>`;
        }).join('');

        $topCashiersList.html(html);
        $topCashierEl.text(cashiers[0] ? cashiers[0].cashier_name : 'N/A');
    }

    function loadSalesReport() {
        $.ajax({
            url: 'assets/getSalesData.php',
            type: 'GET',
            dataType: 'json',
            data: {
                from_date: $fromDateInput.val(),
                to_date: $toDateInput.val(),
                cashier_id: $cashierSelect.val(),
                period: $trendPeriodSelect.val()
            },
            success: function (data) {
                updateSummary(data.summary);
                renderTopCashiers(data.top_cashiers);

                const labels = data.report_data.map(function (row) {
                    return row.date;
                });
                const values = data.report_data.map(function (row) {
                    return row.sales_total;
                });

                createLineChart(labels, values, $trendPeriodSelect.val());

                const pieLabels = data.payment_breakdown.map(function (item) {
                    return item.method;
                });
                const pieValues = data.payment_breakdown.map(function (item) {
                    return item.sales_total;
                });

                createPieChart(pieLabels, pieValues);

                const productLabels = data.top_products.map(function (item) {
                    return item.product_name;
                });
                const productValues = data.top_products.map(function (item) {
                    return item.total_quantity;
                });
                createTopProductsChart(productLabels, productValues);

                const categoryLabels = data.top_categories.map(function (item) {
                    return item.category_name;
                });
                const categoryValues = data.top_categories.map(function (item) {
                    return item.total_quantity;
                });
                createTopCategoriesChart(categoryLabels, categoryValues);
            },
            error: function (xhr, status, error) {
                console.error(error);
                alert('Unable to load sales report. Please try again.');
            }
        });
    }

    $refreshBtn.on('click', function () {
        loadSalesReport();
    });

    $trendPeriodSelect.on('change', function () {
        loadSalesReport();
    });

    loadSalesReport();
});