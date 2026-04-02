$(function() {
    const cart = [];
    let currentCategory = 'all';

    function formatMoney(value) {
        return '₱' + Number(value).toFixed(2);
    }

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

    let activeCategory = 'all';
    let currentSearch = '';

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

    function findCartItem(id) {
        return cart.find(item => item.id.toString() === id.toString());
    }

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

    function removeCartItem(id) {
        const index = cart.findIndex(item => item.id.toString() === id.toString());
        if (index !== -1) {
            cart.splice(index, 1);
            updateCartDisplay();
        }
    }

    function showPopup(message, success = true) {
        const $popup = $('#popup-message');
        $popup.removeClass('hidden').text(message).toggleClass('success', success).toggleClass('error', !success);
        setTimeout(() => $popup.addClass('hidden'), 3500);
    }

    $('.products-container').on('click', '.add-to-cart', function() {
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

    $('#cart-items').on('click', '.qty-btn', function() {
        const id = $(this).data('id');
        if ($(this).hasClass('increase')) {
            changeQuantity(id, 1);
        } else {
            changeQuantity(id, -1);
        }
    });

    $('#cart-items').on('click', '.remove-btn', function() {
        const id = $(this).data('id');
        removeCartItem(id);
    });

    $('.category-buttons').on('click', '.category-btn', function() {
        const selectedCategory = $(this).data('category');
        loadProducts(selectedCategory, currentSearch);
    });

    $('.search-form').on('submit', function(e) {
        e.preventDefault();
        const query = $(this).find('input[name="query"]').val().trim();
        loadProducts(activeCategory, query);
    });

    $('#checkout-btn').on('click', function() {
        if (cart.length === 0) {
            showPopup('Cart is empty.', false);
            return;
        }
        $('#checkout-modal').removeClass('hidden');
        updateTotals($('#discount-type').val());
        const details = cart.map(item => `<div class="modal-item"><span>${item.name}</span><span>${item.quantity} x ${formatMoney(item.price)}</span></div>`).join('');
        $('#checkout-details').html(details);
        $('#cash-given').val('');
        updateCashGivenField();
    });

    function updateChangeDue() {
        const total = Number($('#checkout-total').text().replace('₱', '')) || 0;
        const cashGiven = parseFloat($('#cash-given').val());
        const change = isNaN(cashGiven) ? 0 : cashGiven - total;
        $('#change-due').text(formatMoney(Math.max(change, 0)));
    }

    function updateCashGivenField() {
        const paymentMethod = $('#payment-method').val();

        if (paymentMethod === 'Cash') {
            $('#cash-given').prop('readonly', false).val('');
        } else {
            $('#cash-given').prop('readonly', true).val('');
        }

        updateChangeDue();
    }

    $('#payment-method').on('change', function() {
        updateCashGivenField();
    });

    $('#discount-type').on('change', function() {
        updateTotals($(this).val());
        updateCashGivenField();
    });

    $('#cash-given').on('input', function() {
        updateChangeDue();
    });

    $('#cancel-checkout').on('click', function() {
        $('#checkout-modal').addClass('hidden');
    });

    $('#confirm-checkout').on('click', function() {
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
    updateCartDisplay();
    loadProducts(activeCategory, currentSearch);
});