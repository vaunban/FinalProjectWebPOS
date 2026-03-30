
$(function() {
    // Cached modal and table elements for reuse.
    const $stockModal = $('#stockModal');
    const $productModal = $('#productModal');
    const $editModal = $('#editModal');
    const $inventoryTable = $('.inventory-table');
    const $selectionToolbar = $('#selectionToolbar');
    const $selectAllCheckbox = $('#selectAll');
    let currentBulkAction = null;

    // Open or close the overlay modals by toggling the active class.
    const openModal = ($modal) => $modal.addClass('active');
    const closeModals = () => {
        $stockModal.removeClass('active');
        $productModal.removeClass('active');
        $editModal.removeClass('active');
    };

    $('#openStockModalButton').on('click', () => openModal($stockModal));
    $('#openProductModalButton').on('click', () => openModal($productModal));

    $(document).on('click', '.close-btn', closeModals);

    $(window).on('click', event => {
        if (event.target === $stockModal[0] || event.target === $productModal[0] || event.target === $editModal[0]) {
            closeModals();
        }
    });

    const updateSelectionCount = () => {
        const selectedCount = $('.row-select:checked').length;
        $('#selectionCount').text(`${selectedCount} selected`);
    };

    const clearSelection = () => {
        currentBulkAction = null;
        $inventoryTable.removeClass('selection-active');
        $selectionToolbar.addClass('hidden');
        $('.row-select').prop('checked', false);
        $selectAllCheckbox.prop('checked', false);
        updateSelectionCount();
    };

    const resetEditForm = () => {
        $('#editProductIds').empty();
        $('#editProductForm')[0].reset();
        $('#editProductName, #editProductPrice, #editProductStock, #editProductCategory, #editProductStatus').val('');
    };

    const setEditIds = (ids) => {
        const $container = $('#editProductIds');
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

    // Submit any form using AJAX, including file uploads via FormData.
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
                    // Reload the page after a successful change to refresh the table.
                    location.reload();
                } else {
                    alert(response.message || 'An error occurred.');
                }
            },
            error: function() {
                alert('Request failed. Please try again.');
            }
        });
    };

    $('#addStockForm').on('submit', function(event) {
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

    $('#editProductForm').on('submit', function(event) {
        event.preventDefault();
        ajaxSubmitForm($(this));
    });

    // Build hidden inputs for delete requests when not using AJAX directly.
    const setDeleteInputs = (ids) => {
        const $container = $('#productDeleteInputs');
        $container.empty();
        ids.forEach(id => {
            $('<input>', { type: 'hidden', name: 'ids[]', value: id }).appendTo($container);
        });
    };

    // Delete product(s) via AJAX and reload on success.
    const ajaxDelete = (ids) => {
        $.ajax({
            url: './assets/removeProduct.php',
            type: 'POST',
            dataType: 'json',
            data: { ids: ids },
            success: function(response) {
                if (response && response.success) {
                    alert(response.message || 'Deleted successfully.');
                    location.reload();
                } else {
                    alert(response.message || 'Unable to delete selected items.');
                }
            },
            error: function() {
                alert('Delete request failed.');
            }
        });
    };

    const enterSelectionMode = (action) => {
        currentBulkAction = action;
        $inventoryTable.addClass('selection-active');
        $selectionToolbar.removeClass('hidden');
        $('#selectionModeTitle').text(action === 'edit' ? 'Select rows to edit' : 'Select rows to delete');
        $('#selectionConfirmButton').text(action === 'edit' ? 'Edit selected' : 'Delete selected');
        updateSelectionCount();
    };

    $('#bulkEditButton').on('click', () => enterSelectionMode('edit'));
    $('#bulkDeleteButton').on('click', () => enterSelectionMode('delete'));
    $('#selectionCancelButton').on('click', clearSelection);

    $selectAllCheckbox.on('change', function() {
        $('.row-select').prop('checked', $(this).prop('checked'));
        updateSelectionCount();
    });

    $(document).on('change', '.row-select', updateSelectionCount);

    $('#selectionConfirmButton').on('click', function() {
        const selectedIds = $('.row-select:checked').map(function() {
            return $(this).data('id');
        }).get();

        if (!selectedIds.length) {
            alert('Please select at least one row first.');
            return;
        }

        if (currentBulkAction === 'delete') {
            if (!confirm(`Delete ${selectedIds.length} selected item(s)?`)) {
                return;
            }
            ajaxDelete(selectedIds);
            clearSelection();
            return;
        }

        if (currentBulkAction === 'edit') {
            openEditModal(selectedIds);
            clearSelection();
        }
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

    $(document).on('click', '.product-delete-button', function() {
        const productId = $(this).data('id');
        if (!confirm(`Delete product ID ${productId}?`)) {
            return;
        }
        ajaxDelete([productId]);
    });
});

