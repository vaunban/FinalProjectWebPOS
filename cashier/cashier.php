<?php
/**
 * cashier.php
 * Main cashier POS (Point of Sale) page for the MERKADO system.
 * Requires an active login session — redirects unauthenticated users to the login page.
 * Renders the full cashier interface including:
 *   - A product search bar and category filter buttons
 *   - A product grid (populated via productread.php)
 *   - A live cart/order panel with subtotal, discount, and total
 *   - A checkout modal for confirming payment details
 *   - A popup notification element for success/error messages
 */

session_start();

// If the user is not logged in, redirect to the login page
if(!isset($_SESSION['username']) || !isset($_SESSION['id'])){
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- POS stylesheet -->
    <link rel="stylesheet" href="cashiercss/posstyle.css">
    <!-- jQuery library for DOM manipulation and AJAX -->
    <script src="../jquery-4.0.0.min.js"></script>
    <!-- jsPDF and jsPDF-AutoTable for receipt generation -->
    <script src="cashierjs/jspdf.umd.min.js"></script>
    <script src="cashierjs/jspdf.plugin.autotable.min.js"></script>
</head>
<body>

    <!-- Top navigation bar with branding and logout link -->
    <nav class="navbar">
        <div class="logo">MERKADO</div>
        <div class="navbaritems">
            <a href="cashierassets/cashierlogout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <main class="content">
        <!-- Left panel: product search, category filters, and product grid -->
        <section class="left-panel">

            <!-- Search form — filters products by keyword via live JS search -->
            <form class="search-form" action="#" method="GET">
                <input type="text" name="query" placeholder="Search items..." aria-label="Search items" autocomplete="off">
            </form>
            
            <!-- Category filter buttons — "All" is active by default -->
            <div class="category-section">
                <div class="category-buttons">
                    <button class='category-btn active' data-category='all'>All</button>
                        <?php
                            // Render dynamic category buttons from the database
                            include("cashierassets/categread.php");
                        ?>
                </div>
            </div>

            <!-- Product cards container — populated by productread.php and refreshed via AJAX -->
            <div class="products-container">
                <?php 
                    // Initial product load on page render
                    include("cashierassets/productread.php"); 
                ?>
            </div>
        </section>

        <!-- Right panel: current order summary and checkout button -->
        <section class="right-panel">
            <div class="order-panel">
                <div class="order-panel-header">
                    <h2>Current Order</h2>
                    <p>Items added to the cart will appear here.</p>
                </div>

                <!-- Cart item list — dynamically populated by posscript.js -->
                <div id="cart-items" class="cart-items">
                    <p class="empty-cart">No items added yet. Click a product to build the order.</p>
                </div>

                <!-- Live order price summary — updated by posscript.js → updateTotals() -->
                <div class="order-summary">
                    <div class="summary-row"><span>Subtotal</span><span id="subtotal">₱0.00</span></div>
                    <div class="summary-row"><span>Discount</span><span id="discount-amount">₱0.00</span></div>
                    <div class="summary-row total-row"><span>Total</span><span id="total">₱0.00</span></div>
                </div>

                <!-- Checkout button — disabled until at least one item is in the cart -->
                <button id="checkout-btn" class="checkout-btn" disabled>Proceed to Checkout</button>
            </div>
        </section>
    </main>

    <!-- Checkout modal — shown when the cashier clicks "Proceed to Checkout" -->
    <div id="checkout-modal" class="modal hidden">
        <div class="modal-content">
            <h2>Confirm Checkout</h2>

            <!-- Dynamically populated cart item summary shown inside the modal -->
            <div id="checkout-details" class="checkout-details"></div>

            <!-- Guest checkout toggle — when checked, customer fields are hidden and skipped -->
            <div class="modal-field guest-toggle-field">
                <label class="guest-checkbox-label">
                    <input type="checkbox" id="guest-checkout" />
                    Guest Checkout (skip customer info)
                </label>
            </div>

            <!-- Customer information fields — required unless Guest Checkout is checked -->
            <div class="modal-field" id="customer-fields">
                <label for="customer-name">Customer Name <span class="required-star">*</span></label>
                <input id="customer-name" type="text" placeholder="Enter customer name" />
                <label for="contact-number">Contact Number</label>
                <input id="contact-number" type="text" placeholder="Enter contact number" />
            </div>

            <!-- Payment method selector (Cash, E-Wallet, Debit/Credit) -->
            <div class="modal-field">
                <label for="payment-method">Payment Method</label>
                <select id="payment-method">
                    <option value="Cash">Cash</option>
                    <option value="E-Wallet">E-Wallet</option>
                    <option value="Debit/Credit">Debit/Credit</option>
                </select>
            </div>

            <!-- Discount type selector (None, PWD 5%, Senior Citizen 5%) -->
             <div class="modal-field">
                <label for="discount-type">Discount</label>
                <select id="discount-type">
                    <option value="none">None</option>
                    <option value="pwd">PWD - 5%</option>
                    <option value="senior">Senior Citizen - 5%</option>
                </select>
            </div>

            <!-- Cash given input — only editable when payment method is "Cash" -->
            <div class="modal-field" id="cash-given-field">
                <label for="cash-given">Cash Given</label>
                <input id="cash-given" type="number" min="0" step="0.01" placeholder="Enter cash received" />
            </div>

            <!-- Modal price breakdown: subtotal, discount, total, and change due -->
            <div class="modal-totals">
                <div class="summary-row"><span>Subtotal</span><span id="checkout-subtotal">₱0.00</span></div>
                <div class="summary-row"><span>Discount</span><span id="checkout-discount">₱0.00</span></div>
                <div class="summary-row total-row"><span>Total</span><span id="checkout-total">₱0.00</span></div>
                <div class="summary-row"><span>Change Due</span><span id="change-due">₱0.00</span></div>
            </div>

            <!-- Modal action buttons: confirm payment or cancel -->
            <div class="modal-actions">
                <button id="confirm-checkout" class="confirm-btn">Confirm Payment</button>
                <button id="cancel-checkout" class="cancel-btn" type="button">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Floating popup notification for success and error messages (managed by posscript.js) -->
    <div id="popup-message" class="popup-message hidden"></div>

    <!-- Main POS JavaScript — cache-busted with a PHP timestamp to always load the latest version -->
    <script src="cashierjs/posscript.js?v=<?php echo time(); ?>"></script>
</body>
</html>