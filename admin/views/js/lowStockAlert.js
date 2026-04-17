/**
 * lowStockAlert.js
 * Shows a floating popup notification in the bottom-right corner
 * when products have low stock (less than 20 units).
 * Automatically detects the correct API URL based on the current page path.
 * Used on all admin pages (admin.php, dashboard.php, inventory.php, etc.).
 */

(function() {
    /**
     * Determines the correct URL for the getLowStock.php API
     * based on the current page's location in the directory structure.
     * @returns {string} The relative URL to getLowStock.php
     */
    function getLowStockUrl() {
        const path = window.location.pathname;
        if (path.includes('/admin/controllers/')) {
            return '../models/getLowStock.php';
        }
        if (path.includes('/admin/models/')) {
            return 'getLowStock.php';
        }
        if (path.includes('/cashier/')) {
            return '../../admin/models/getLowStock.php';
        }
        return 'admin/models/getLowStock.php';
    }

    /**
     * Injects the CSS styles for the low stock popup into the page head.
     * Only runs once (checks for existing style element by ID).
     */
    function createStyles() {
        if (document.getElementById('low-stock-alert-styles')) return;
        const style = document.createElement('style');
        style.id = 'low-stock-alert-styles';
        style.textContent = `
            /* Popup container — fixed in bottom-right corner */
            .low-stock-alert-popup {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: min(360px, calc(100vw - 32px));
                z-index: 9999;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .low-stock-alert-popup.hidden {
                display: none;
            }
            /* Card styling with frosted glass effect */
            .low-stock-popup-card {
                background: rgba(255,255,255,0.98);
                border-radius: 18px;
                box-shadow: 0 24px 48px rgba(15, 23, 42, 0.2);
                padding: 18px;
                border: 1px solid rgba(116, 123, 142, 0.14);
                backdrop-filter: blur(10px);
            }
            /* Header with title and badge */
            .low-stock-popup-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 12px;
                gap: 12px;
            }
            .low-stock-popup-title {
                font-size: 1rem;
                font-weight: 700;
                color: #24324a;
            }
            /* Red badge showing the count of low stock items */
            .low-stock-popup-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #c0392b;
                color: #fff;
                border-radius: 999px;
                padding: 4px 10px;
                font-size: 0.8rem;
                font-weight: 700;
            }
            .low-stock-popup-body {
                margin-top: 8px;
            }
            .low-stock-popup-message {
                margin: 0 0 12px;
                color: #4a5568;
                font-size: 0.95rem;
            }
            /* Scrollable list of low stock products */
            .low-stock-popup-list {
                list-style: none;
                margin: 0;
                padding: 0;
                max-height: 240px;
                overflow-y: auto;
            }
            .low-stock-popup-list li {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid rgba(149, 157, 165, 0.16);
            }
            .low-stock-popup-list li:last-child {
                border-bottom: none;
            }
            .low-stock-popup-list span {
                color: #1f2937;
                font-weight: 600;
            }
            .low-stock-popup-list strong {
                color: #b91c1c;
                font-weight: 700;
            }
            /* Close button (×) */
            .low-stock-popup-close {
                appearance: none;
                border: none;
                background: transparent;
                color: #4b5563;
                font-size: 1.25rem;
                cursor: pointer;
                line-height: 1;
                padding: 2px 6px;
            }
            .low-stock-alert-popup::before {
                content: '';
                position: absolute;
                inset: 0;
                pointer-events: none;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Creates the popup DOM element and appends it to the page body.
     * Sets up click handlers for the close button and backdrop click.
     * @returns {HTMLElement} The popup container element
     */
    function createPopupElement() {
        let popup = document.getElementById('low-stock-alert-popup');
        if (popup) return popup;

        popup = document.createElement('div');
        popup.id = 'low-stock-alert-popup';
        popup.className = 'low-stock-alert-popup hidden';
        popup.innerHTML = `
            <div class="low-stock-popup-card">
                <div class="low-stock-popup-header">
                    <div>
                        <div class="low-stock-popup-title">Low Stock Alert</div>
                    </div>
                    <div>
                        <span class="low-stock-popup-badge">0</span>
                        <button type="button" class="low-stock-popup-close" aria-label="Close alert">&times;</button>
                    </div>
                </div>
                <div class="low-stock-popup-body">
                    <p class="low-stock-popup-message">Some products are running low and need attention.</p>
                    <ul class="low-stock-popup-list"></ul>
                </div>
            </div>
        `;

        document.body.appendChild(popup);

        // Close popup when × button is clicked
        popup.querySelector('.low-stock-popup-close').addEventListener('click', function() {
            popup.classList.add('hidden');
        });

        // Close popup when clicking outside the card
        popup.addEventListener('click', function(event) {
            if (event.target === popup) {
                popup.classList.add('hidden');
            }
        });
        return popup;
    }

    /**
     * Populates the popup with low stock item data and makes it visible.
     * Hides the popup if no items are provided.
     * @param {Array} items - Array of objects with product_name and current_stock
     */
    function showLowStockPopup(items) {
        const popup = createPopupElement();
        const listEl = popup.querySelector('.low-stock-popup-list');
        const badgeEl = popup.querySelector('.low-stock-popup-badge');

        if (!items || !items.length) {
            popup.classList.add('hidden');
            return;
        }

        // Update the badge count and render the list (max 10 items)
        badgeEl.textContent = `${items.length}`;
        listEl.innerHTML = items.slice(0, 10).map(function(item) {
            return `<li><span>${item.product_name}</span><strong>${item.current_stock} left</strong></li>`;
        }).join('');
        popup.classList.remove('hidden');
    }

    /**
     * Fetches low stock data from the server and shows the popup if items are found.
     */
    function loadLowStockItems() {
        const url = getLowStockUrl();
        fetch(url)
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Low stock fetch failed');
                }
                return response.json();
            })
            .then(function(data) {
                if (Array.isArray(data) && data.length > 0) {
                    showLowStockPopup(data);
                }
            })
            .catch(function(error) {
                console.error('Low stock alert failed:', error);
            });
    }

    // Initialize when the page is ready
    document.addEventListener('DOMContentLoaded', function() {
        createStyles();
        loadLowStockItems();
    });
})();