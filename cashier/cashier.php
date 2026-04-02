<?php
session_start();

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
    <link rel="stylesheet" href="cashiercss/posstyle.css">
    <script src="../jquery-4.0.0.min.js"></script>
</head>
<body>

    <nav class="navbar">
        <div class="logo">MERKADO</div>
        <div class="navbaritems">
            <a href="cashierassets/cashierlogout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <main class="content">
        <section class="left-panel">
            <form class="search-form" action="#" method="GET">
                <input type="text" name="query" placeholder="Search items..." aria-label="Search items" autocomplete="off">
                <button type="submit">Search</button>
            </form>
            
                    <div class="category-section">
                        <div class="category-buttons">
                            <button class='category-btn active' data-category='all'>All</button>
                                <?php
                                    include("cashierassets/categread.php");
                                ?>
                        </div>
                    </div>

            <div class="products-container">
                <?php 
                    include("cashierassets/productread.php"); 
                ?>
            </div>
        </section>
        <section class="right-panel">
            <div class="order-panel">
                <div class="order-panel-header">
                    <h2>Current Order</h2>
                    <p>Items added to the cart will appear here.</p>
                </div>

                <div id="cart-items" class="cart-items">
                    <p class="empty-cart">No items added yet. Click a product to build the order.</p>
                </div>

                <div class="order-summary">
                    <div class="summary-row"><span>Subtotal</span><span id="subtotal">₱0.00</span></div>
                    <div class="summary-row"><span>Discount</span><span id="discount-amount">₱0.00</span></div>
                    <div class="summary-row total-row"><span>Total</span><span id="total">₱0.00</span></div>
                </div>

                <button id="checkout-btn" class="checkout-btn" disabled>Proceed to Checkout</button>
            </div>
        </section>
    </main>

    <div id="checkout-modal" class="modal hidden">
        <div class="modal-content">
            <h2>Confirm Checkout</h2>
            <div id="checkout-details" class="checkout-details"></div>

            <div class="modal-field">
                <label for="payment-method">Payment Method</label>
                <select id="payment-method">
                    <option value="Cash">Cash</option>
                    <option value="E-Wallet">E-Wallet</option>
                    <option value="Debit/Credit">Debit/Credit</option>
                </select>
            </div>

            <div class="modal-field" id="cash-given-field">
                <label for="cash-given">Cash Given</label>
                <input id="cash-given" type="number" min="0" step="0.01" placeholder="Enter cash received" />
            </div>

            <div class="modal-field">
                <label for="discount-type">Discount</label>
                <select id="discount-type">
                    <option value="none">None</option>
                    <option value="pwd">PWD - 5%</option>
                    <option value="senior">Senior Citizen - 5%</option>
                </select>
            </div>

            <div class="modal-totals">
                <div class="summary-row"><span>Subtotal</span><span id="checkout-subtotal">₱0.00</span></div>
                <div class="summary-row"><span>Discount</span><span id="checkout-discount">₱0.00</span></div>
                <div class="summary-row total-row"><span>Total</span><span id="checkout-total">₱0.00</span></div>
                <div class="summary-row"><span>Change Due</span><span id="change-due">₱0.00</span></div>
            </div>

            <div class="modal-actions">
                <button id="confirm-checkout" class="confirm-btn">Confirm Payment</button>
                <button id="cancel-checkout" class="cancel-btn" type="button">Cancel</button>
            </div>
        </div>
    </div>

    <div id="popup-message" class="popup-message hidden"></div>

    <script src="cashierjs/posscript.js"></script>
</body>
</html>