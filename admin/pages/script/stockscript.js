
const stockModal = document.getElementById("stockModal");
const openStockModalButton = document.getElementById("openStockModalButton");
const productModal = document.getElementById("productModal");
const openProductModalButton = document.getElementById("openProductModalButton");
const bulkEditButton = document.getElementById("bulkEditButton");
const bulkDeleteButton = document.getElementById("bulkDeleteButton");
const selectionToolbar = document.getElementById("selectionToolbar");
const selectionModeTitle = document.getElementById("selectionModeTitle");
const selectionCount = document.getElementById("selectionCount");
const selectionConfirmButton = document.getElementById("selectionConfirmButton");
const selectionCancelButton = document.getElementById("selectionCancelButton");
const selectAllCheckbox = document.getElementById("selectAll");
const inventoryTable = document.querySelector(".inventory-table");
const productDeleteForm = document.getElementById('productDeleteForm');
const productDeleteInputs = document.getElementById('productDeleteInputs');
const editModal = document.getElementById('editModal');
const editProductForm = document.getElementById('editProductForm');
const editProductIds = document.getElementById('editProductIds');
const editProductName = document.getElementById('editProductName');
const editProductPrice = document.getElementById('editProductPrice');
const editProductStock = document.getElementById('editProductStock');
const editProductCategory = document.getElementById('editProductCategory');
const editProductStatus = document.getElementById('editProductStatus');
let currentBulkAction = null;

openStockModalButton.addEventListener("click", () => {
  stockModal.classList.add("active");
});

openProductModalButton.addEventListener("click", () => {
  productModal.classList.add("active");
});

document.querySelectorAll('.close-btn').forEach(btn => {
  btn.addEventListener("click", () => {
    stockModal.classList.remove("active");
    productModal.classList.remove("active");
    editModal.classList.remove("active");
  });
});

window.addEventListener("click", event => {
  if (event.target === stockModal) {
    stockModal.classList.remove("active");
  }
  if (event.target === productModal) {
    productModal.classList.remove("active");
  }
  if (event.target === editModal) {
    editModal.classList.remove("active");
  }
});

const updateSelectionCount = () => {
  const selected = document.querySelectorAll('.row-select:checked');
  selectionCount.textContent = `${selected.length} selected`;
};

const clearSelection = () => {
  currentBulkAction = null;
  inventoryTable.classList.remove('selection-active');
  selectionToolbar.classList.add('hidden');
  document.querySelectorAll('.row-select').forEach(checkbox => {
    checkbox.checked = false;
  });
  if (selectAllCheckbox) {
    selectAllCheckbox.checked = false;
  }
  updateSelectionCount();
};

const resetEditForm = () => {
  editProductIds.innerHTML = '';
  editProductName.value = '';
  editProductPrice.value = '';
  editProductStock.value = '';
  editProductCategory.value = '';
  editProductStatus.value = '';
  editProductForm.reset();
};

const setEditIds = (ids) => {
  editProductIds.innerHTML = '';
  ids.forEach(id => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'ids[]';
    input.value = id;
    editProductIds.appendChild(input);
  });
};

const openEditModal = (ids, product = null) => {
  resetEditForm();
  setEditIds(ids);
  if (product) {
    editProductName.value = product.name;
    editProductPrice.value = product.price;
    editProductStock.value = product.stock;
    editProductCategory.value = product.categoryId;
    editProductStatus.value = product.status;
  }
  editModal.classList.add('active');
};

const setDeleteIds = (ids) => {
  productDeleteInputs.innerHTML = '';
  ids.forEach(id => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'ids[]';
    input.value = id;
    productDeleteInputs.appendChild(input);
  });
};

const enterSelectionMode = (action) => {
  currentBulkAction = action;
  inventoryTable.classList.add('selection-active');
  selectionToolbar.classList.remove('hidden');
  selectionModeTitle.textContent = action === 'edit' ? 'Select rows to edit' : 'Select rows to delete';
  selectionConfirmButton.textContent = action === 'edit' ? 'Edit selected' : 'Delete selected';
  updateSelectionCount();
};

if (bulkEditButton) {
  bulkEditButton.addEventListener('click', () => enterSelectionMode('edit'));
}

if (bulkDeleteButton) {
  bulkDeleteButton.addEventListener('click', () => enterSelectionMode('delete'));
}

if (selectionCancelButton) {
  selectionCancelButton.addEventListener('click', clearSelection);
}

if (selectAllCheckbox) {
  selectAllCheckbox.addEventListener('change', () => {
    document.querySelectorAll('.row-select').forEach(checkbox => {
      checkbox.checked = selectAllCheckbox.checked;
    });
    updateSelectionCount();
  });
}

document.addEventListener('change', event => {
  if (event.target.matches('.row-select')) {
    updateSelectionCount();
  }
});

if (selectionConfirmButton) {
  selectionConfirmButton.addEventListener('click', () => {
    const selectedIds = Array.from(document.querySelectorAll('.row-select:checked')).map(box => box.dataset.id);
    if (selectedIds.length === 0) {
      alert('Please select at least one row first.');
      return;
    }

    if (currentBulkAction === 'delete') {
      const confirmed = confirm(`Delete ${selectedIds.length} selected item(s)?`);
      if (!confirmed) {
        return;
      }
      setDeleteIds(selectedIds);
      productDeleteForm.submit();
      return;
    }

    if (currentBulkAction === 'edit') {
      openEditModal(selectedIds);
      clearSelection();
      return;
    }
  });
}

document.addEventListener('click', event => {
  if (event.target.matches('.product-edit-button')) {
    const product = {
      name: event.target.dataset.name || '',
      price: event.target.dataset.price || '',
      stock: event.target.dataset.stock || '',
      categoryId: event.target.dataset.categoryId || '',
      status: event.target.dataset.status || ''
    };
    openEditModal([event.target.dataset.id], product);
  }

  if (event.target.matches('.product-delete-button')) {
    const productId = event.target.dataset.id;
    const confirmed = confirm(`Delete product ID ${productId}?`);
    if (!confirmed) {
      return;
    }
    setDeleteIds([productId]);
    productDeleteForm.submit();
  }
});


