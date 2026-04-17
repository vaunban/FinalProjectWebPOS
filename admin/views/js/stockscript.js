/**
 * stockscript.js
 * Admin Inventory page JavaScript for the MERKADO system.
 * Handles all inventory management interactions including:
 *   - Tab switching (Products, Archive, Categories, Category Archive)
 *   - AJAX loading of product and category data
 *   - Modal management (Add Stock, Add Product, Edit Product, Add Category, Edit Category)
 *   - Single and bulk edit/delete operations
 *   - Product and category archive/restore operations
 *   - Sorting controls
 *
 * Dependencies: jQuery (loaded in inventory.php)
 */

$(function() {
    // --- Cached DOM elements ---

    // Modal references
    const $stockModal = $('#stockModal');
    const $productModal = $('#productModal');
    const $editModal = $('#editModal');
    const $categoryModal = $('#categoryModal');
    const $editCategoryModal = $('#editCategoryModal');

    // Tab content sections
    const $inventorySection = $('#inventorySection');
    const $archiveSection = $('#archiveSection');
    const $categorySection = $('#categorySection');
    const $categoryArchiveSection = $('#categoryArchiveSection');

    // Action button groups (shown/hidden based on active tab)
    const $inventoryActions = $('#inventoryActions');
    const $categoryActions = $('#categoryActions');
    const $sortControls = $('.sort-controls');
    
    // Tab buttons
    const $inventoryTab = $('#inventoryTab');
    const $archiveTab = $('#archiveTab');
    const $categoryTab = $('#categoryTab');
    const $categoryArchiveTab = $('#categoryArchiveTab');

    // Table containers and content areas
    const $inventoryTable = $('#inventorySection');
    const $categoryTable = $('#categorySection');
    const $inventoryTableContent = $('#inventoryTableContent');
    const $archiveTableContent = $('#archiveTableContent');
    
    // Sort controls
    const $sortField = $('#sortField');
    const $sortDirection = $('#sortDirection');
    const $applySortButton = $('#applySortButton');

    // Bulk selection toolbar
    const $selectionToolbar = $('#selectionToolbar');
    
    // Track the current bulk action mode and active tab
    let currentBulkAction = null;
    let currentView = 'inventory';

    // --- Modal helpers ---

    /** Opens a modal by adding the 'active' class */
    const openModal = ($modal) => $modal.addClass('active');

    /** Closes all modals by removing the 'active' class */
    const closeModals = () => {
        $stockModal.removeClass('active');
        $productModal.removeClass('active');
        $editModal.removeClass('active');
        $categoryModal.removeClass('active');
        $editCategoryModal.removeClass('active');
    };

    // Open modal button handlers
    $('#openStockModalButton').on('click', () => openModal($stockModal));
    $('#openProductModalButton').on('click', () => openModal($productModal));
    $('#openCategoryModalButton').on('click', () => openModal($categoryModal));

    // Close modal when × button is clicked
    $(document).on('click', '.close-btn', closeModals);

    // Close modal when clicking outside the modal content (on the backdrop)
    $(window).on('click', event => {
        if (event.target === $stockModal[0] || event.target === $productModal[0] || event.target === $editModal[0] || event.target === $categoryModal[0] || event.target === $editCategoryModal[0]) {
            closeModals();
        }
    });

    // --- Selection mode helpers ---

    /** Updates the "X selected" counter in the bulk selection toolbar */
    const updateSelectionCount = () => {
        let selectedCount = 0;
        if (currentView === 'inventory') {
            selectedCount = $('.row-select:checked').length;
        } else if (currentView === 'category') {
            selectedCount = $('.row-select-cat:checked').length;
        }
        $('#selectionCount').text(`${selectedCount} selected`);
    };

    /** Exits selection mode: unchecks all rows and hides the toolbar */
    const clearSelection = () => {
        currentBulkAction = null;
        $inventoryTable.removeClass('selection-active');
        $categoryTable.removeClass('selection-active');
        $selectionToolbar.addClass('hidden');
        $('.row-select, .row-select-cat').prop('checked', false);
        $('#selectAll, #selectAllCat').prop('checked', false);
        updateSelectionCount();
    };

    // --- Edit form helpers ---

    /** Resets the product edit form to blank */
    const resetEditForm = () => {
        $('#editProductIds').empty();
        $('#editProductForm')[0].reset();
        $('#editProductName, #editProductPrice, #editProductStock, #editProductCategory, #editProductStatus').val('');
    };
    
    /** Resets the category edit form to blank */
    const resetCatEditForm = () => {
        $('#editCategoryIds').empty();
        $('#editCategoryForm')[0].reset();
        $('#editCatName').val('');
    };

    /** Adds hidden input fields for the product IDs being edited */
    const setEditIds = (ids) => {
        const $container = $('#editProductIds');
        $container.empty();
        ids.forEach(id => {
            $('<input>', { type: 'hidden', name: 'ids[]', value: id }).appendTo($container);
        });
    };
    
    /** Adds hidden input fields for the category IDs being edited */
    const setCatEditIds = (ids) => {
        const $container = $('#editCategoryIds');
        $container.empty();
        ids.forEach(id => {
            $('<input>', { type: 'hidden', name: 'ids[]', value: id }).appendTo($container);
        });
    };

    /**
     * Opens the product edit modal with the given IDs.
     * Pre-fills the form if a single product's data is provided.
     */
    const openEditModal = (ids, product = null) => {
        resetEditForm();
        setEditIds(ids);

        if (product) {
            $('#editProductName').val(product.name);
            $('#editProductPrice').val(product.price);
            $('#editProductStock').val(product.stock);
            $('#editProductCategory').val(product.categoryId);
            $('#editProductStatus').val(product.status);
        }

        $editModal.addClass('active');
    };
    
    /**
     * Opens the category edit modal with the given IDs.
     * Pre-fills the form if a single category's data is provided.
     */
    const openCatEditModal = (ids, cat = null) => {
        resetCatEditForm();
        setCatEditIds(ids);

        if (cat) {
            $('#editCatName').val(cat.name);
        }

        $editCategoryModal.addClass('active');
    };

    // --- AJAX data loaders ---

    /**
     * Loads category data from the server via AJAX.
     * @param {string} view - 'categories' for active, 'archive' for archived
     */
    const loadCategories = (view) => {
        $.ajax({
            url: '../models/getCategories.php',
            type: 'GET',
            dataType: 'json',
            data: { view: view },
            success: function(response) {
                if (response && response.success) {
                    if (view === 'archive') {
                        $('#categoryArchiveTableContent').html(response.html);
                    } else {
                        $('#categoryTableContent').html(response.html);
                    }
                }
            }
        });
    };

    /**
     * Loads product data from the server via AJAX with current sort settings.
     * @param {string} view - 'inventory' for active products, 'archive' for archived
     */
    const loadProducts = (view) => {
        currentView = view;
        $.ajax({
            url: '../models/getProducts.php',
            type: 'GET',
            dataType: 'json',
            data: {
                view: view,
                sortField: $sortField.val(),
                sortDirection: $sortDirection.val()
            },
            success: function(response) {
                if (response && response.success) {
                    if (view === 'archive') {
                        $archiveTableContent.html(response.html);
                    } else {
                        $inventoryTableContent.html(response.html);
                    }
                } else {
                    alert(response.message || 'Unable to load products.');
                }
            },
            error: function() {
                alert('Unable to load products. Please try again.');
            }
        });
    };

    // --- AJAX form submission ---

    /**
     * Submits a form via AJAX (used for add/edit stock, product, and category forms).
     * On success: reloads the relevant data and closes the modal.
     */
    const ajaxSubmitForm = ($form) => {
        const formData = new FormData($form[0]);

        $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method') || 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(response) {
                if (response && response.success) {
                    alert(response.message || 'Saved successfully.');
                    if (currentView === 'category' || currentView === 'categoryArchive') {
                        loadCategories(currentView === 'categoryArchive' ? 'archive' : 'categories');
                        closeModals();
                    } else {
                        loadProducts(currentView);
                        closeModals();
                    }
                } else {
                    alert(response.message || 'An error occurred.');
                }
            },
            error: function() {
                alert('Request failed. Please try again.');
            }
        });
    };

    // Intercept form submissions and use AJAX instead
    $('#addStockForm, #editProductForm, #addCategoryForm, #editCategoryForm').on('submit', function(event) {
        event.preventDefault();
        ajaxSubmitForm($(this));
    });

    // Add Product form requires image validation before submitting
    $('#addProductForm').on('submit', function(event) {
        event.preventDefault();
        const fileInput = $('#product_image')[0];
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            alert('Please choose a product image to upload.');
            return;
        }
        ajaxSubmitForm($(this));
    });

    // --- AJAX delete/restore operations ---

    /** Adds hidden inputs for product IDs in the delete form */
    const setDeleteInputs = (ids) => {
        const $container = $('#productDeleteInputs');
        $container.empty();
        ids.forEach(id => {
            $('<input>', { type: 'hidden', name: 'ids[]', value: id }).appendTo($container);
        });
    };

    /** Deletes one or more products via AJAX */
    const ajaxDelete = (ids) => {
        $.ajax({
            url: '../models/removeProduct.php',
            type: 'POST',
            dataType: 'json',
            data: { ids: ids },
            success: function(response) {
                if (response && response.success) {
                    alert(response.message || 'Deleted successfully.');
                    loadProducts(currentView);
                } else {
                    alert(response.message || 'Unable to delete selected items.');
                }
            },
            error: function() {
                alert('Delete request failed.');
            }
        });
    };

    /** Deletes one or more categories via AJAX */
    const ajaxCategoryDelete = (ids) => {
        $.ajax({
            url: '../models/removeCategory.php',
            type: 'POST',
            dataType: 'json',
            data: { ids: ids },
            success: function(response) {
                if (response && response.success) {
                    alert(response.message || 'Deleted successfully.');
                    loadCategories('categories');
                } else {
                    alert(response.message || 'Unable to delete selected items.');
                }
            },
            error: function() {
                alert('Delete request failed.');
            }
        });
    };

    /** Restores a product from the archive via AJAX */
    const ajaxRestore = (archiveId) => {
        $.ajax({
            url: '../models/restoreProduct.php',
            type: 'POST',
            dataType: 'json',
            data: { ids: [archiveId] },
            success: function(response) {
                if (response && response.success) {
                    alert(response.message || 'Product restored successfully.');
                    loadProducts('archive');
                    if (currentView === 'inventory') {
                        loadProducts('inventory');
                    }
                } else {
                    alert(response.message || 'Unable to restore product.');
                }
            },
            error: function() {
                alert('Restore request failed.');
            }
        });
    };
    
    /** Restores a category from the archive via AJAX */
    const ajaxCategoryRestore = (archiveId) => {
        $.ajax({
            url: '../models/restoreCategory.php',
            type: 'POST',
            dataType: 'json',
            data: { ids: [archiveId] },
            success: function(response) {
                if (response && response.success) {
                    alert(response.message || 'Category restored successfully.');
                    loadCategories('archive');
                    if (currentView === 'category') {
                        loadCategories('categories');
                    }
                    // Also refresh product tables since categories affect product display
                    loadProducts('inventory');
                    loadProducts('archive');
                } else {
                    alert(response.message || 'Unable to restore category.');
                }
            },
            error: function() {
                alert('Restore request failed.');
            }
        });
    };

    // --- Bulk selection mode ---

    /**
     * Enters selection mode for bulk edit or delete.
     * Shows checkboxes and the selection toolbar.
     * @param {string} action - 'edit' or 'delete'
     */
    const enterSelectionMode = (action) => {
        currentBulkAction = action;
        if(currentView === 'inventory') {
            $inventoryTable.addClass('selection-active');
        } else if (currentView === 'category') {
            $categoryTable.addClass('selection-active');
        }
        
        $selectionToolbar.removeClass('hidden');
        $('#selectionModeTitle').text(action === 'edit' ? 'Select rows to edit' : 'Select rows to delete');
        $('#selectionConfirmButton').text(action === 'edit' ? 'Edit selected' : 'Delete selected');
        updateSelectionCount();
    };

    // Bulk action button handlers
    $('#bulkEditButton').on('click', () => enterSelectionMode('edit'));
    $('#bulkDeleteButton').on('click', () => enterSelectionMode('delete'));
    $('#bulkCategoryDeleteButton').on('click', () => enterSelectionMode('delete'));
    $('#selectionCancelButton').on('click', clearSelection);

    // "Select All" checkbox handlers for products and categories
    $(document).on('change', '#selectAll', function() {
        $('.row-select').prop('checked', $(this).prop('checked'));
        updateSelectionCount();
    });
    
    $(document).on('change', '#selectAllCat', function() {
        $('.row-select-cat').prop('checked', $(this).prop('checked'));
        updateSelectionCount();
    });

    // Update count whenever individual checkboxes change
    $(document).on('change', '.row-select, .row-select-cat', updateSelectionCount);

    // Confirm button handler for bulk actions
    $('#selectionConfirmButton').on('click', function() {
        let selectedIds = [];
        
        // Collect selected IDs based on current view
        if (currentView === 'inventory') {
            selectedIds = $('.row-select:checked').map(function() {
                return $(this).data('id');
            }).get();
        } else if (currentView === 'category') {
            selectedIds = $('.row-select-cat:checked').map(function() {
                return $(this).data('id');
            }).get();
        }

        if (!selectedIds.length) {
            alert('Please select at least one row first.');
            return;
        }

        // Execute the bulk action (delete or edit)
        if (currentBulkAction === 'delete') {
            if (!confirm(`Delete ${selectedIds.length} selected item(s)?`)) {
                return;
            }
            if(currentView === 'inventory') {
                ajaxDelete(selectedIds);
            } else if (currentView === 'category') {
                ajaxCategoryDelete(selectedIds);
            }
            clearSelection();
            return;
        }

        if (currentBulkAction === 'edit') {
            if(currentView === 'inventory') {
                openEditModal(selectedIds);
            } else if (currentView === 'category') {
                openCatEditModal(selectedIds);
            }
            clearSelection();
        }
    });

    // --- Tab switching ---

    /**
     * Switches the active tab, showing the correct section and action buttons.
     * Also loads fresh data for the selected tab.
     * @param {string} tab - 'inventory', 'archive', 'category', or 'categoryArchive'
     */
    const switchTab = (tab) => {
        currentView = tab;
        
        // Hide all sections
        $inventorySection.addClass('hidden');
        $archiveSection.addClass('hidden');
        $categorySection.addClass('hidden');
        $categoryArchiveSection.addClass('hidden');
        
        // Hide all action button groups
        $inventoryActions.addClass('hidden');
        $sortControls.addClass('hidden');
        $categoryActions.addClass('hidden');
        
        // Remove active state from all tabs
        $inventoryTab.removeClass('active');
        $archiveTab.removeClass('active');
        $categoryTab.removeClass('active');
        $categoryArchiveTab.removeClass('active');
        
        // Show the selected tab's content and load its data
        if (tab === 'archive') {
            $archiveSection.removeClass('hidden');
            $archiveTab.addClass('active');
            $('#overviewTitle').text('Product Archive');
            $('#overviewDesc').text('View previously deleted products stored in the archive.');
            clearSelection();
            loadProducts('archive');
            return;
        }
        
        if (tab === 'category') {
            $categorySection.removeClass('hidden');
            $categoryActions.removeClass('hidden');
            $categoryTab.addClass('active');
            $('#overviewTitle').text('Category Inventory');
            $('#overviewDesc').text('Manage your product categories.');
            clearSelection();
            loadCategories('categories');
            return;
        }
        
        if (tab === 'categoryArchive') {
            $categoryArchiveSection.removeClass('hidden');
            $categoryArchiveTab.addClass('active');
            $('#overviewTitle').text('Category Archive');
            $('#overviewDesc').text('View previously deleted categories stored in the archive.');
            clearSelection();
            loadCategories('archive');
            return;
        }

        // Default: show product inventory
        $inventorySection.removeClass('hidden');
        $inventoryActions.removeClass('hidden');
        $sortControls.removeClass('hidden');
        $inventoryTab.addClass('active');
        $('#overviewTitle').text('Stock Overview');
        $('#overviewDesc').text('Review all product quantities and statuses. Use the buttons above to update stock or add new items.');
        clearSelection();
        loadProducts('inventory');
    };

    // Tab click handlers
    $inventoryTab.on('click', () => switchTab('inventory'));
    $archiveTab.on('click', () => switchTab('archive'));
    $categoryTab.on('click', () => switchTab('category'));
    $categoryArchiveTab.on('click', () => switchTab('categoryArchive'));
    
    // Apply sort button handler
    $applySortButton.on('click', () => loadProducts(currentView));

    // --- Single-row action handlers (delegated) ---

    // Restore a product from archive
    $(document).on('click', '.restore-archive-button', function() {
        const archiveId = $(this).data('archive-id');
        if (!confirm('Restore this product from the archive?')) {
            return;
        }
        ajaxRestore(archiveId);
    });
    
    // Restore a category from archive
    $(document).on('click', '.restore-category-archive-button', function() {
        const archiveId = $(this).data('archive-id');
        if (!confirm('Restore this category from the archive?')) {
            return;
        }
        ajaxCategoryRestore(archiveId);
    });

    // Edit a single product (reads data attributes from the Edit button)
    $(document).on('click', '.product-edit-button', function() {
        const $button = $(this);
        openEditModal([$button.data('id')], {
            name: $button.data('name') || '',
            price: $button.data('price') || '',
            stock: $button.data('stock') || '',
            categoryId: $button.data('categoryId') || '',
            status: $button.data('status') || ''
        });
    });
    
    // Edit a single category
    $(document).on('click', '.category-edit-button', function() {
        const $button = $(this);
        openCatEditModal([$button.data('id')], {
            name: $button.data('name') || ''
        });
    });

    // Delete a single product
    $(document).on('click', '.product-delete-button', function() {
        const productId = $(this).data('id');
        if (!confirm(`Delete product ID ${productId}?`)) {
            return;
        }
        ajaxDelete([productId]);
    });
    
    // Delete a single category
    $(document).on('click', '.category-delete-button', function() {
        const catId = $(this).data('id');
        if (!confirm(`Delete category ID ${catId}?`)) {
            return;
        }
        ajaxCategoryDelete([catId]);
    });
});
