$(function() {
    // Cached modal and table elements for reuse.
    const $stockModal = $('#stockModal');
    const $productModal = $('#productModal');
    const $editModal = $('#editModal');
    
    // Category Modals
    const $categoryModal = $('#categoryModal');
    const $editCategoryModal = $('#editCategoryModal');

    // Sections
    const $inventorySection = $('#inventorySection');
    const $archiveSection = $('#archiveSection');
    const $categorySection = $('#categorySection');
    const $categoryArchiveSection = $('#categoryArchiveSection');

    // Controls
    const $inventoryActions = $('#inventoryActions');
    const $categoryActions = $('#categoryActions');
    const $sortControls = $('.sort-controls');
    
    // Tabs
    const $inventoryTab = $('#inventoryTab');
    const $archiveTab = $('#archiveTab');
    const $categoryTab = $('#categoryTab');
    const $categoryArchiveTab = $('#categoryArchiveTab');

    const $inventoryTable = $('#inventorySection');
    const $categoryTable = $('#categorySection');
    const $inventoryTableContent = $('#inventoryTableContent');
    const $archiveTableContent = $('#archiveTableContent');
    
    const $sortField = $('#sortField');
    const $sortDirection = $('#sortDirection');
    const $applySortButton = $('#applySortButton');
    const $selectionToolbar = $('#selectionToolbar');
    
    let currentBulkAction = null;
    let currentView = 'inventory'; // active tab

    // Open or close the overlay modals by toggling the active class.
    const openModal = ($modal) => $modal.addClass('active');
    const closeModals = () => {
        $stockModal.removeClass('active');
        $productModal.removeClass('active');
        $editModal.removeClass('active');
        $categoryModal.removeClass('active');
        $editCategoryModal.removeClass('active');
    };

    $('#openStockModalButton').on('click', () => openModal($stockModal));
    $('#openProductModalButton').on('click', () => openModal($productModal));
    $('#openCategoryModalButton').on('click', () => openModal($categoryModal));

    $(document).on('click', '.close-btn', closeModals);

    $(window).on('click', event => {
        if (event.target === $stockModal[0] || event.target === $productModal[0] || event.target === $editModal[0] || event.target === $categoryModal[0] || event.target === $editCategoryModal[0]) {
            closeModals();
        }
    });

    const updateSelectionCount = () => {
        let selectedCount = 0;
        if (currentView === 'inventory') {
            selectedCount = $('.row-select:checked').length;
        } else if (currentView === 'category') {
            selectedCount = $('.row-select-cat:checked').length;
        }
        $('#selectionCount').text(`${selectedCount} selected`);
    };

    const clearSelection = () => {
        currentBulkAction = null;
        $inventoryTable.removeClass('selection-active');
        $categoryTable.removeClass('selection-active');
        $selectionToolbar.addClass('hidden');
        $('.row-select, .row-select-cat').prop('checked', false);
        $('#selectAll, #selectAllCat').prop('checked', false);
        updateSelectionCount();
    };

    const resetEditForm = () => {
        $('#editProductIds').empty();
        $('#editProductForm')[0].reset();
        $('#editProductName, #editProductPrice, #editProductStock, #editProductCategory, #editProductStatus').val('');
    };
    
    const resetCatEditForm = () => {
        $('#editCategoryIds').empty();
        $('#editCategoryForm')[0].reset();
        $('#editCatName').val('');
    };

    const setEditIds = (ids) => {
        const $container = $('#editProductIds');
        $container.empty();
        ids.forEach(id => {
            $('<input>', { type: 'hidden', name: 'ids[]', value: id }).appendTo($container);
        });
    };
    
    const setCatEditIds = (ids) => {
        const $container = $('#editCategoryIds');
        $container.empty();
        ids.forEach(id => {
            $('<input>', { type: 'hidden', name: 'ids[]', value: id }).appendTo($container);
        });
    };

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
    
    const openCatEditModal = (ids, cat = null) => {
        resetCatEditForm();
        setCatEditIds(ids);

        if (cat) {
            $('#editCatName').val(cat.name);
        }

        $editCategoryModal.addClass('active');
    };

    const loadCategories = (view) => {
        $.ajax({
            url: './assets/getCategories.php',
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

    const loadProducts = (view) => {
        // Kept this function to maintain compatibility if they click sort on the product pages
        currentView = view;
        $.ajax({
            url: './assets/getProducts.php',
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

    $('#addStockForm, #editProductForm, #addCategoryForm, #editCategoryForm').on('submit', function(event) {
        event.preventDefault();
        ajaxSubmitForm($(this));
    });

    $('#addProductForm').on('submit', function(event) {
        event.preventDefault();
        const fileInput = $('#product_image')[0];
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            alert('Please choose a product image to upload.');
            return;
        }
        ajaxSubmitForm($(this));
    });

    const setDeleteInputs = (ids) => {
        const $container = $('#productDeleteInputs');
        $container.empty();
        ids.forEach(id => {
            $('<input>', { type: 'hidden', name: 'ids[]', value: id }).appendTo($container);
        });
    };

    const ajaxDelete = (ids) => {
        $.ajax({
            url: './assets/removeProduct.php',
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

    const ajaxCategoryDelete = (ids) => {
        $.ajax({
            url: './assets/removeCategory.php',
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

    const ajaxRestore = (archiveId) => {
        $.ajax({
            url: './assets/restoreProduct.php',
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
    
    const ajaxCategoryRestore = (archiveId) => {
        $.ajax({
            url: './assets/restoreCategory.php',
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

    $('#bulkEditButton').on('click', () => enterSelectionMode('edit'));
    $('#bulkDeleteButton').on('click', () => enterSelectionMode('delete'));
    $('#bulkCategoryDeleteButton').on('click', () => enterSelectionMode('delete'));
    $('#selectionCancelButton').on('click', clearSelection);

    $(document).on('change', '#selectAll', function() {
        $('.row-select').prop('checked', $(this).prop('checked'));
        updateSelectionCount();
    });
    
    $(document).on('change', '#selectAllCat', function() {
        $('.row-select-cat').prop('checked', $(this).prop('checked'));
        updateSelectionCount();
    });

    $(document).on('change', '.row-select, .row-select-cat', updateSelectionCount);

    $('#selectionConfirmButton').on('click', function() {
        let selectedIds = [];
        
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

    const switchTab = (tab) => {
        currentView = tab;
        
        // Hide all sections initially
        $inventorySection.addClass('hidden');
        $archiveSection.addClass('hidden');
        $categorySection.addClass('hidden');
        $categoryArchiveSection.addClass('hidden');
        
        // Hide all actions
        $inventoryActions.addClass('hidden');
        $sortControls.addClass('hidden');
        $categoryActions.addClass('hidden');
        
        // Unset active on all tabs
        $inventoryTab.removeClass('active');
        $archiveTab.removeClass('active');
        $categoryTab.removeClass('active');
        $categoryArchiveTab.removeClass('active');
        
        if (tab === 'archive') {
            $archiveSection.removeClass('hidden');
            $archiveTab.addClass('active');
            $('#overviewTitle').text('Product Archive');
            $('#overviewDesc').text('View previously deleted products stored in the archive.');
            clearSelection();
            return;
        }
        
        if (tab === 'category') {
            $categorySection.removeClass('hidden');
            $categoryActions.removeClass('hidden');
            $categoryTab.addClass('active');
            $('#overviewTitle').text('Category Inventory');
            $('#overviewDesc').text('Manage your product categories.');
            clearSelection();
            return;
        }
        
        if (tab === 'categoryArchive') {
            $categoryArchiveSection.removeClass('hidden');
            $categoryArchiveTab.addClass('active');
            $('#overviewTitle').text('Category Archive');
            $('#overviewDesc').text('View previously deleted categories stored in the archive.');
            clearSelection();
            return;
        }

        // Default: inventory
        $inventorySection.removeClass('hidden');
        $inventoryActions.removeClass('hidden');
        $sortControls.removeClass('hidden');
        $inventoryTab.addClass('active');
        $('#overviewTitle').text('Stock Overview');
        $('#overviewDesc').text('Review all product quantities and statuses. Use the buttons above to update stock or add new items.');
        clearSelection();
    };

    $inventoryTab.on('click', () => switchTab('inventory'));
    $archiveTab.on('click', () => switchTab('archive'));
    $categoryTab.on('click', () => switchTab('category'));
    $categoryArchiveTab.on('click', () => switchTab('categoryArchive'));
    
    $applySortButton.on('click', () => loadProducts(currentView));

    $(document).on('click', '.restore-archive-button', function() {
        const archiveId = $(this).data('archive-id');
        if (!confirm('Restore this product from the archive?')) {
            return;
        }
        ajaxRestore(archiveId);
    });
    
    $(document).on('click', '.restore-category-archive-button', function() {
        const archiveId = $(this).data('archive-id');
        if (!confirm('Restore this category from the archive?')) {
            return;
        }
        ajaxCategoryRestore(archiveId);
    });

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
    
    $(document).on('click', '.category-edit-button', function() {
        const $button = $(this);
        openCatEditModal([$button.data('id')], {
            name: $button.data('name') || ''
        });
    });

    $(document).on('click', '.product-delete-button', function() {
        const productId = $(this).data('id');
        if (!confirm(`Delete product ID ${productId}?`)) {
            return;
        }
        ajaxDelete([productId]);
    });
    
    $(document).on('click', '.category-delete-button', function() {
        const catId = $(this).data('id');
        if (!confirm(`Delete category ID ${catId}?`)) {
            return;
        }
        ajaxCategoryDelete([catId]);
    });
});
