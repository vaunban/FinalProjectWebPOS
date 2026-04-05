/**
 * transactionscript.js
 * Admin Transactions page JavaScript for the MERKADO system.
 * Handles fetching, displaying, filtering, and paginating transaction records.
 * Also provides a modal view for individual transaction details.
 *
 * Dependencies: jQuery (loaded in transactions.php)
 * Called from:  admin/pages/transactions.php
 */

$(document).ready(function () {
    // Tracks which page of results is currently being viewed
    let currentPage = 1;

    /**
     * Fetches transaction data from the server via AJAX.
     * Reads the current filter values (date range, cashier, payment method)
     * and the current page number, then sends them to gettransaction.php.
     * On success, updates the table, pagination, and summary cards.
     */
    function loadTransactions() {
        const fromDate = $('#dateFrom').val();
        const toDate = $('#dateTo').val();
        const cashierId = $('#filterCashier').val();
        const paymentMethod = $('#filterPayment').val();

        $.ajax({
            url: 'assets/gettransaction.php',
            type: 'GET',
            data: {
                from_date: fromDate,
                to_date: toDate,
                cashier_id: cashierId,
                payment_method: paymentMethod,
                page: currentPage
            },
            dataType: 'json',
            success: function (data) {
                displayTransactions(data.transactions);
                updatePagination(data.total_pages, data.current_page);
                updateSummary(data.summary);
            },
            error: function () {
                $('#transactionsBody').html('<tr><td colspan="7">Error loading transactions</td></tr>');
            }
        });
    }

    /**
     * Renders transaction rows into the table body.
     * Shows a "No transactions found" message if the array is empty.
     * Attaches click handlers to each "View" button to open the detail modal.
     * @param {Array} transactions - Array of transaction objects from the server.
     */
    function displayTransactions(transactions) {
        if (transactions.length === 0) {
            $('#transactionsBody').html('<tr><td colspan="7">No transactions found</td></tr>');
            return;
        }

        let html = '';
        transactions.forEach(function (t) {
            const date = new Date(t.created_at).toLocaleString();
            // Show "Walk-in" for guest transactions (no customer record)
            const customerName = t.customer_name ? t.customer_name : 'Walk-in';
            html += `
                <tr>
                    <td>${t.receipt_number}</td>
                    <td>${t.cashier_name}</td>
                    <td>${customerName}</td>
                    <td>₱${parseFloat(t.total_amount).toFixed(2)}</td>
                    <td>${t.payment_method.toUpperCase()}</td>
                    <td>${date}</td>
                    <td><button class="view-btn" data-id="${t.id}">View</button></td>
                </tr>
            `;
        });
        $('#transactionsBody').html(html);

        // Bind click event on newly rendered "View" buttons
        $('.view-btn').click(function () {
            const id = $(this).data('id');
            viewTransaction(id);
        });
    }

    /**
     * Builds and renders pagination buttons below the transactions table.
     * Highlights the active page and attaches click handlers to navigate pages.
     * @param {number} totalPages  - Total number of pages available.
     * @param {number} currentPage - The currently active page number.
     */
    function updatePagination(totalPages, activePage) {
        let html = '';
        for (let i = 1; i <= totalPages; i++) {
            html += `<button class="page-btn ${i === activePage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }
        $('#pagination').html(html);

        // Navigate to the selected page when a pagination button is clicked
        $('.page-btn').click(function () {
            currentPage = $(this).data('page');
            loadTransactions();
        });
    }

    /**
     * Updates the three summary cards (Total Transactions, Total Sales, Average)
     * with data returned from the server.
     * @param {Object} summary - Object with total_count, total_sales, avg_amount.
     */
    function updateSummary(summary) {
        $('#totalTransactions').text(summary.total_count || 0);
        $('#totalSales').text('₱' + parseFloat(summary.total_sales || 0).toFixed(2));
        $('#avgTransaction').text('₱' + parseFloat(summary.avg_amount || 0).toFixed(2));
    }

    /**
     * Fetches the full details of a single transaction (header + line items)
     * from transactiondetail.php and opens the detail modal on success.
     * @param {number} id - The transaction ID to look up.
     */
    function viewTransaction(id) {
        $.ajax({
            url: 'assets/transactiondetail.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function (data) {
                displayModal(data);
            },
            error: function () {
                alert('Error loading transaction details');
            }
        });
    }

    /**
     * Populates and shows the transaction detail modal.
     * Displays receipt info, a table of purchased items with subtotals,
     * a tax line (12%), and the grand total.
     * Also binds the Close button and backdrop click to hide the modal.
     * @param {Object} data - Object containing `transaction` and `items` arrays.
     */
    function displayModal(data) {
        const t = data.transaction;
        const items = data.items;
        const date = new Date(t.created_at).toLocaleString();
        const customerName = t.customer_name ? t.customer_name : 'Walk-in';

        // Build the line-items table
        let itemsHtml = '<table class="modal-items-table"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead><tbody>';
        let subtotal = 0;

        items.forEach(function (item) {
            const itemSubtotal = item.quantity * item.price;
            subtotal += itemSubtotal;
            itemsHtml += `
                <tr>
                    <td>${item.product_name}</td>
                    <td>${item.quantity}</td>
                    <td>₱${parseFloat(item.price).toFixed(2)}</td>
                    <td>₱${itemSubtotal.toFixed(2)}</td>
                </tr>
            `;
        });

        // Calculate 12% tax and append summary section
        const tax = subtotal * 0.12;

        itemsHtml += `</tbody></table>
            <div class="modal-summary">
                <div class="summary-line">Subtotal: ₱${subtotal.toFixed(2)}</div>
                <div class="summary-line">Tax (12%): ₱${tax.toFixed(2)}</div>
                <div class="summary-line total">TOTAL: ₱${parseFloat(t.total_amount).toFixed(2)}</div>
            </div>
        `;

        // Build the full modal body with transaction header info + items table
        const modalHtml = `
            <div class="transaction-info">
                <p><strong>Receipt #:</strong> ${t.receipt_number}</p>
                <p><strong>Date:</strong> ${date}</p>
                <p><strong>Cashier:</strong> ${t.cashier_name}</p>
                <p><strong>Customer:</strong> ${customerName}</p>
                <p><strong>Payment Method:</strong> ${t.payment_method.toUpperCase()}</p>
            </div>
            ${itemsHtml}
            <div class="modal-buttons">
                <button id="closeModalBtn">Close</button>
            </div>
        `;

        $('#modalBody').html(modalHtml);
        $('#viewModal').show();

        // Close modal via the Close button or the × icon in the header
        $('#closeModalBtn, .close').click(function () {
            $('#viewModal').hide();
        });
    }

    // --- Event Handlers ---

    /**
     * Event handler: Applies filters and reloads from page 1
     * when the "Search" button is clicked.
     */
    $('#searchBtn').click(function () {
        currentPage = 1;
        loadTransactions();
    });

    /**
     * Event handler: Clears all filter inputs and reloads from page 1
     * when the "Reset" button is clicked.
     */
    $('#resetBtn').click(function () {
        $('#dateFrom').val('');
        $('#dateTo').val('');
        $('#filterCashier').val('');
        $('#filterPayment').val('');
        currentPage = 1;
        loadTransactions();
    });

    /**
     * Event handler: Closes the modal when the user clicks
     * outside of the modal content (on the backdrop overlay).
     */
    $(window).click(function (e) {
        if ($(e.target).is('#viewModal')) {
            $('#viewModal').hide();
        }
    });

    // Initial load — fetch and display the first page of transactions
    loadTransactions();
});