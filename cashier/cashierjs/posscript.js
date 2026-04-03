$(function () {
    // In-memory array that holds the items currently in the cart
    const cart = [];
    let currentCategory = 'all';

    /**
     * Formats a numeric value as a Philippine Peso currency string.
     * @param {number} value - The numeric amount to format.
     * @returns {string} - The formatted currency string (e.g., "₱12.50").
     */
    function formatMoney(value) {
        return '₱' + Number(value).toFixed(2);
    }

    /**
     * Re-renders the cart item list in the UI based on the current cart array.
     * Shows an empty-cart message if the cart is empty, otherwise builds
     * HTML for each cart item with increase/decrease/remove controls.
     * Also enables or disables the checkout button accordingly,
     * and calls updateTotals() to refresh the price summary.
     */
    function updateCartDisplay() {
        const $cartItems = $('#cart-items');
        $cartItems.empty();

        if (cart.length === 0) {
            $cartItems.append('<p class="empty-cart">No items added yet. Click a product to build the order.</p>');
            $('#checkout-btn').prop('disabled', true);
        } else {
            cart.forEach(item => {
                const itemHtml = `
                    <div class="cart-item" data-id="${item.id}">
                        <div class="item-name">${item.name}</div>
                        <div class="item-meta">
                            <span>${formatMoney(item.price)} x ${item.quantity}</span>
                            <div class="item-actions">
                                <button class="qty-btn decrease" data-id="${item.id}">-</button>
                                <button class="qty-btn increase" data-id="${item.id}">+</button>
                                <button class="remove-btn" data-id="${item.id}">Remove</button>
                            </div>
                        </div>
                    </div>
                `;
                $cartItems.append(itemHtml);
            });
            $('#checkout-btn').prop('disabled', false);
        }
        updateTotals();
    }

    // Tracks the currently active category filter and search query
    let activeCategory = 'all';
    let currentSearch = '';

    /**
     * Fetches and displays the product list via AJAX from the server.
     * Highlights the selected category button and applies the search query filter.
     * Updates the .products-container with the returned HTML on success,
     * or shows an error message on failure.
     * @param {string} [category='all'] - The product category to filter by.
     * @param {string} [query=''] - The search keyword to filter products by name.
     */
    function loadProducts(category = 'all', query = '') {
        activeCategory = category;
        currentSearch = query;
        $('.category-btn').removeClass('active');
        $(`.category-btn[data-category="${category}"]`).addClass('active');

        $.ajax({
            url: 'cashierassets/productread.php',
            method: 'GET',
            dataType: 'html',
            data: { category, query },
            success(html) {
                $('.products-container').html(html);
            },
            error() {
                $('.products-container').html('<div class="product-message">Unable to load products.</div>');
            }
        });
    }

    /**
     * Calculates the subtotal, discount amount, and total from the current cart,
     * then updates all matching price display elements in both the sidebar
     * summary and the checkout modal.
     * Also triggers updateChangeDue() and updateCashGivenField() to keep
     * the cash/change fields in sync.
     * @param {string} [discountType='none'] - The discount type applied ('none', 'pwd', or 'senior').
     */
    function updateTotals(discountType = 'none') {
        const subtotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
        const discountRate = discountType === 'pwd' || discountType === 'senior' ? 0.05 : 0;
        const discountAmount = subtotal * discountRate;
        const total = subtotal - discountAmount;

        $('#subtotal').text(formatMoney(subtotal));
        $('#discount-amount').text(formatMoney(discountAmount));
        $('#total').text(formatMoney(total));
        $('#checkout-subtotal').text(formatMoney(subtotal));
        $('#checkout-discount').text(formatMoney(discountAmount));
        $('#checkout-total').text(formatMoney(total));
        updateChangeDue();
        updateCashGivenField();
    }

    /**
     * Searches the cart array for an item that matches the given product ID.
     * @param {string|number} id - The product ID to search for.
     * @returns {Object|undefined} - The matching cart item object, or undefined if not found.
     */
    function findCartItem(id) {
        return cart.find(item => item.id.toString() === id.toString());
    }

    /**
     * Adjusts the quantity of a cart item by a given delta value (+1 or -1).
     * Prevents the quantity from exceeding the available stock or dropping below 1.
     * Shows a popup warning if the requested quantity exceeds stock.
     * Refreshes the cart display after a valid change.
     * @param {string|number} id - The ID of the cart item to update.
     * @param {number} delta - The amount to change the quantity by (positive or negative).
     */
    function changeQuantity(id, delta) {
        const item = findCartItem(id);
        if (!item) return;
        const newQuantity = item.quantity + delta;

        if (newQuantity > item.stock) {
            showPopup('Not enough stock available. Remaining stock: ' + item.stock, false);
            return;
        }

        item.quantity = Math.max(1, newQuantity);
        updateCartDisplay();
    }

    /**
     * Removes an item from the cart array by its product ID.
     * Refreshes the cart display after removal.
     * @param {string|number} id - The ID of the cart item to remove.
     */
    function removeCartItem(id) {
        const index = cart.findIndex(item => item.id.toString() === id.toString());
        if (index !== -1) {
            cart.splice(index, 1);
            updateCartDisplay();
        }
    }

    /**
     * Displays a temporary popup notification message to the user.
     * Applies a 'success' or 'error' CSS class based on the outcome,
     * and automatically hides the popup after 3.5 seconds.
     * @param {string} message - The text message to display in the popup.
     * @param {boolean} [success=true] - Whether the popup represents a success (true) or error (false).
     */
    function showPopup(message, success = true) {
        const $popup = $('#popup-message');
        $popup.removeClass('hidden').text(message).toggleClass('success', success).toggleClass('error', !success);
        setTimeout(() => $popup.addClass('hidden'), 3500);
    }

    /**
     * Event handler: Adds a product to the cart when the "Add to Cart" button is clicked.
     * Reads product data (ID, name, price, stock) from the button's data attributes.
     * If the item is already in the cart, increments its quantity;
     * otherwise, adds a new entry. Checks stock before adding and shows
     * a popup warning if stock is insufficient.
     */
    $('.products-container').on('click', '.add-to-cart', function () {
        const $button = $(this);
        const productId = $button.data('product-id');
        const name = $button.data('product-name');
        const price = parseFloat($button.data('product-price')) || 0;
        const stock = parseInt($button.data('product-stock')) || 0;

        let item = findCartItem(productId);
        if (item) {
            if (item.quantity + 1 > stock) {
                showPopup('Not enough stock available. Remaining stock: ' + stock, false);
                return;
            }
            item.quantity += 1;
        } else {
            if (1 > stock) {
                showPopup('Not enough stock available. Remaining stock: ' + stock, false);
                return;
            }
            cart.push({ id: productId, name, price, quantity: 1, stock: stock });
        }
        updateCartDisplay();
    });

    /**
     * Event handler: Increases or decreases a cart item's quantity
     * when the "+" or "-" quantity buttons are clicked in the cart panel.
     * Delegates click detection to determine which button (increase/decrease) was pressed.
     */
    $('#cart-items').on('click', '.qty-btn', function () {
        const id = $(this).data('id');
        if ($(this).hasClass('increase')) {
            changeQuantity(id, 1);
        } else {
            changeQuantity(id, -1);
        }
    });

    /**
     * Event handler: Removes a cart item when the "Remove" button is clicked
     * within the cart item list.
     */
    $('#cart-items').on('click', '.remove-btn', function () {
        const id = $(this).data('id');
        removeCartItem(id);
    });

    /**
     * Event handler: Filters the product list by category when a category button is clicked.
     * Passes the selected category and the current search query to loadProducts().
     */
    $('.category-buttons').on('click', '.category-btn', function () {
        const selectedCategory = $(this).data('category');
        loadProducts(selectedCategory, currentSearch);
    });

    /**
     * Event handler: Submits the product search form.
     * Prevents default form submission, reads the search input value,
     * and calls loadProducts() with the active category and trimmed query string.
     */
    $('.search-form').on('submit', function (e) {
        e.preventDefault();
        const query = $(this).find('input[name="query"]').val().trim();
        loadProducts(activeCategory, query);
    });

    /**
     * Event handler: Opens the checkout modal when the "Proceed to Checkout" button is clicked.
     * Validates that the cart is not empty, refreshes the totals based on the selected discount,
     * renders a summary of cart items in the modal, and resets the cash input field.
     */
    $('#checkout-btn').on('click', function () {
        if (cart.length === 0) {
            showPopup('Cart is empty.', false);
            return;
        }
        // Reset guest checkbox and show customer fields every time the modal opens
        $('#guest-checkout').prop('checked', false);
        $('#customer-fields').show();
        $('#customer-name').val('');
        $('#contact-number').val('');
        $('#checkout-modal').removeClass('hidden');
        updateTotals($('#discount-type').val());
        const details = cart.map(item => `<div class="modal-item"><span>${item.name}</span><span>${item.quantity} x ${formatMoney(item.price)}</span></div>`).join('');
        $('#checkout-details').html(details);
        $('#cash-given').val('');
        updateCashGivenField();
    });

    /**
     * Calculates the change due to the customer and updates the "Change Due" display.
     * Reads the total from the checkout modal and the cash amount entered by the user.
     * Displays ₱0.00 when the cash given is less than or equal to the total.
     */
    function updateChangeDue() {
        const total = Number($('#checkout-total').text().replace('₱', '')) || 0;
        const cashGiven = parseFloat($('#cash-given').val());
        const change = isNaN(cashGiven) ? 0 : cashGiven - total;
        $('#change-due').text(formatMoney(Math.max(change, 0)));
    }

    /**
     * Enables or disables the "Cash Given" input field based on the selected payment method.
     * If "Cash" is selected, the field is editable and cleared;
     * for other payment methods (E-Wallet, Debit/Credit), it is set to read-only and cleared.
     * Also triggers updateChangeDue() to refresh the change calculation.
     */
    function updateCashGivenField() {
        const paymentMethod = $('#payment-method').val();

        if (paymentMethod === 'Cash') {
            $('#cash-given').prop('readonly', false).val('');
        } else {
            $('#cash-given').prop('readonly', true).val('');
        }

        updateChangeDue();
    }

    /**
     * Event handler: Responds to changes in the payment method dropdown.
     * Updates the cash given field's editable state and resets change display.
     */
    $('#payment-method').on('change', function () {
        updateCashGivenField();
    });

    /**
     * Event handler: Responds to changes in the discount type dropdown.
     * Recalculates totals using the newly selected discount type
     * and updates the cash given field state.
     */
    $('#discount-type').on('change', function () {
        updateTotals($(this).val());
        updateCashGivenField();
    });

    /**
     * Event handler: Recalculates the change due whenever the cashier
     * types into the "Cash Given" input field.
     */
    $('#cash-given').on('input', function () {
        updateChangeDue();
    });

    /**
     * Event handler: Toggles the customer info fields when the Guest Checkout
     * checkbox is checked or unchecked.
     * When checked: hides the name/contact fields and clears their values.
     * When unchecked: shows the fields again.
     */
    $('#guest-checkout').on('change', function () {
        if ($(this).is(':checked')) {
            $('#customer-fields').hide();
            $('#customer-name').val('');
            $('#contact-number').val('');
        } else {
            $('#customer-fields').show();
        }
    });

    /**
     * Event handler: Closes the checkout modal without processing any payment
     * when the "Cancel" button is clicked.
     */
    $('#cancel-checkout').on('click', function () {
        $('#checkout-modal').addClass('hidden');
    });

    /**
     * Event handler: Processes the transaction when the "Confirm Payment" button is clicked.
     * Validates cash input for Cash payments (must be a positive number >= total).
     * Sends cart data, payment method, discount type, and cash given to the server via AJAX POST.
     * On success: clears the cart, reloads the product list, hides the modal,
     * and shows a success popup with the receipt number and change due (if applicable).
     * On failure: shows an error popup with the server's message or a generic error.
     */
    $('#confirm-checkout').on('click', function () {
        // Read guest checkbox state and customer info
        const isGuest = $('#guest-checkout').is(':checked');
        const customerName = $('#customer-name').val().trim();
        const contactNumber = $('#contact-number').val().trim();

        // Require customer name unless guest checkout is selected
        if (!isGuest && customerName === '') {
            showPopup('Please enter a customer name or select Guest Checkout.', false);
            return;
        }
        const paymentMethod = $('#payment-method').val();
        const discountType = $('#discount-type').val();
        const subtotal = Number($('#checkout-subtotal').text().replace('₱', '')) || 0;
        const discount = Number($('#checkout-discount').text().replace('₱', '')) || 0;
        const total = subtotal - discount;
        let cashGiven = null;

        if (paymentMethod === 'Cash') {
            cashGiven = parseFloat($('#cash-given').val());
            if (isNaN(cashGiven) || cashGiven <= 0) {
                showPopup('Enter a valid cash amount.', false);
                return;
            }
            if (cashGiven < total) {
                showPopup('Cash given must be equal or greater than total.', false);
                return;
            }
        }

        $.ajax({
            url: 'cashierassets/checkout.php',
            method: 'POST',
            dataType: 'json',
            data: {
                cart: JSON.stringify(cart),
                customer_name: customerName,
                contact_number: contactNumber,
                payment_method: paymentMethod,
                discount_type: discountType,
                cash_given: cashGiven
            },
            success(response) {
                if (response.success) {
                    cart.length = 0;
                    updateCartDisplay();
                    loadProducts(activeCategory, currentSearch);
                    $('#checkout-modal').addClass('hidden');
                    $('#cash-given').val('');
                    let message = 'Transaction completed successfully. Receipt: ' + response.receipt_number;
                    if (paymentMethod === 'Cash') {
                        const change = (cashGiven - total).toFixed(2);
                        message += ' Change due: ₱' + change;
                    }
                    showPopup(message, true);
                } else {
                    showPopup(response.message || 'Failed to complete transaction.', false);
                }
            },
            error() {
                showPopup('Server error. Please try again.', false);
            }
        });
    })

    // Initialize the page: render an empty cart display and load the default product list
    updateCartDisplay();
    loadProducts(activeCategory, currentSearch);
});