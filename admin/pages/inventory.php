<?php
// Start session and verify the user is logged in as admin.
// Start the session and verify that an admin user is logged in.
session_start();

if(!isset($_SESSION['username'])){
    header("Location: ../index.php");
    exit();
}

if($_SESSION['role'] != 'admin'){
    header("Location: ../../cashier/cashier.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>
    <link rel="stylesheet" href="./css/inventorystyle.css">

</head>
<body>
    <div class="page-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/merkado-icon.png" alt="MERKADO logo">
                <h2><a href="../admin.php">MERKADO</a></h2>
            </div>
            <ul class="sidebar-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="inventory.php">Inventory</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="accounts.php">Accounts</a></li>
                <li><a href="assets/adminlogout.php">Log Out</a></li>
            </ul>
        </div>

        <main class="content">
            <header class="page-top">
                <div class="page-title-group">
                    <h1 id="pageTitle">Inventory</h1>
                    <p id="pageSubtitle">Manage your product stock, categories, and availability in one place.</p>
                </div>
                <div class="tab-buttons">
                    <button type="button" class="tab-button active" id="inventoryTab">Product Inventory</button>
                    <button type="button" class="tab-button" id="archiveTab">Product Archive</button>
                    <button type="button" class="tab-button" id="categoryTab">Category Inventory</button>
                    <button type="button" class="tab-button" id="categoryArchiveTab">Category Archive</button>
                </div>
                <div class="sort-controls">
                    <div class="sort-group">
                        <label for="sortField">Sort by</label>
                        <select id="sortField">
                            <option value="">Default</option>
                            <option value="category">Category</option>
                            <option value="price">Price</option>
                            <option value="quantity">Quantity</option>
                            <option value="status">Status</option>
                            <option value="name">Alphabetical</option>
                        </select>
                    </div>
                    <div class="sort-group">
                        <label for="sortDirection">Order</label>
                        <select id="sortDirection">
                            <option value="asc">Ascending</option>
                            <option value="desc">Descending</option>
                        </select>
                    </div>
                    <button type="button" id="applySortButton" class="btn btn-secondary">Apply</button>
                </div>
                <!-- Action buttons for Products -->
                <div class="action-buttons" id="inventoryActions">
                    <button type="button" id="openStockModalButton" class="btn btn-secondary">Add Stock</button>
                    <button type="button" id="openProductModalButton" class="btn btn-primary">Add Product</button>
                    <button type="button" id="bulkEditButton" class="btn btn-secondary">Bulk Edit</button>
                    <button type="button" id="bulkDeleteButton" class="btn btn-danger">Bulk Delete</button>
                </div>
                <!-- Action buttons for Categories -->
                <div class="action-buttons hidden" id="categoryActions">
                    <button type="button" id="openCategoryModalButton" class="btn btn-primary">Add Category</button>
                    <button type="button" id="bulkCategoryDeleteButton" class="btn btn-danger">Bulk Delete</button>
                </div>
                
                <div class="selection-toolbar hidden" id="selectionToolbar">
                    <span id="selectionModeTitle">Select rows to apply bulk action</span>
                    <span id="selectionCount">0 selected</span>
                    <button type="button" id="selectionConfirmButton" class="btn btn-primary">Confirm</button>
                    <button type="button" id="selectionCancelButton" class="btn btn-secondary outline">Cancel</button>
                </div>
            </header>

            <section class="inventory-card-row">
                <article class="inventory-card">
                    <h2 id="overviewTitle">Stock Overview</h2>
                    <p id="overviewDesc">Review all product quantities and statuses. Use the buttons above to update stock or add new items.</p>
                </article>
            </section>

            <!-- Product Inventory Section -->
            <section class="inventory-table" id="inventorySection">
                <div id="inventoryTableContent">
                    <?php
                    include (__DIR__ . '/../..//connect.php');
                    $sql = "SELECT p.name AS product_name,p.id,p.price,p.stock_quantity,p.prodStatus,p.category_id,COALESCE(c.name, 'Uncategorized') AS category_name
                    FROM products p
                    LEFT JOIN categories c 
                    ON p.category_id = c.id";
                    $result = $conn->query($sql);
                    if($result->num_rows >0) {
                        echo "<table border = 1>";
                        echo "<tr><th class=\"select-column\"><input type=\"checkbox\" id=\"selectAll\"></th>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Action</th>
                        </tr>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                                    echo "<td class=\"select-column\"><input type=\"checkbox\" class=\"row-select\" data-id=\"" . $row['id'] . "\"></td>";
                            echo "<td>" . $row['id'] . "</td>";
                            echo "<td>" . $row['product_name'] . "</td>";
                            echo "<td>" . $row['price'] . "</td>";
                            echo "<td>" . $row['stock_quantity'] . "</td>";
                            echo "<td>" . $row['category_name'] . "</td>";
                            echo "<td>" . $row['prodStatus'] . "</td>";
                            echo "<td><button type=\"button\" class=\"table-action-button product-edit-button\" data-id=\"" . $row['id'] . "\" data-name=\"" . htmlspecialchars($row['product_name'], ENT_QUOTES) . "\" data-price=\"" . $row['price'] . "\" data-stock=\"" . $row['stock_quantity'] . "\" data-category-id=\"" . $row['category_id'] . "\" data-status=\"" . $row['prodStatus'] . "\">Edit</button> ";
                            echo "<button type=\"button\" class=\"table-action-button product-delete-button delete\" data-id=\"" . $row['id'] . "\">Delete</button></td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<div class=\"empty-state\">No stock data found.</div>";
                    }
                    ?>
                </div>
            </section>

            <!-- Product Archive Section -->
            <section class="inventory-table hidden" id="archiveSection">
                <div id="archiveTableContent">
                    <?php
                    $archiveSql = "SELECT pa.archived_id, pa.id AS product_id, pa.name AS product_name, pa.price, pa.stock_quantity, pa.category_id, pa.prodStatus, pa.archived_at, c.name AS category_name
                    FROM products_archive pa
                    LEFT JOIN categories c ON pa.category_id = c.id
                    ORDER BY pa.archived_at DESC";
                    $archiveResult = @$conn->query($archiveSql);
                    if ($archiveResult && $archiveResult->num_rows > 0) {
                        echo "<table border = 1>";
                        echo "<tr>
                            <th>Archive ID</th>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Archived At</th>
                            <th>Action</th>
                        </tr>";
                        while ($row = $archiveResult->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['archived_id'] . "</td>";
                            echo "<td>" . $row['product_id'] . "</td>";
                            echo "<td>" . $row['product_name'] . "</td>";
                            echo "<td>" . $row['price'] . "</td>";
                            echo "<td>" . $row['stock_quantity'] . "</td>";
                            echo "<td>" . $row['category_name'] . "</td>";
                            echo "<td>" . $row['prodStatus'] . "</td>";
                            echo "<td>" . $row['archived_at'] . "</td>";
                            echo "<td><button type=\"button\" class=\"table-action-button restore-archive-button\" data-archive-id=\"" . $row['archived_id'] . "\">Restore</button></td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<div class=\"empty-state\">No archived products found.</div>";
                    }
                    ?>
                </div>
            </section>

            <!-- Category Inventory Section -->
            <section class="inventory-table hidden" id="categorySection">
                <div id="categoryTableContent">
                    <?php
                    $catSql = "SELECT id, name FROM categories";
                    $catResult = $conn->query($catSql);
                    if($catResult && $catResult->num_rows > 0) {
                        echo "<table border = 1>";
                        echo "<tr><th class=\"select-column\"><input type=\"checkbox\" id=\"selectAllCat\"></th>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Action</th>
                        </tr>";
                        while ($row = $catResult->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td class=\"select-column\"><input type=\"checkbox\" class=\"row-select-cat\" data-id=\"" . $row['id'] . "\"></td>";
                            echo "<td>" . $row['id'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td><button type=\"button\" class=\"table-action-button category-edit-button\" data-id=\"" . $row['id'] . "\" data-name=\"" . htmlspecialchars($row['name'], ENT_QUOTES) . "\">Edit</button> ";
                            echo "<button type=\"button\" class=\"table-action-button category-delete-button delete\" data-id=\"" . $row['id'] . "\">Delete</button></td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<div class=\"empty-state\">No category data found.</div>";
                    }
                    ?>
                </div>
            </section>

            <!-- Category Archive Section -->
            <section class="inventory-table hidden" id="categoryArchiveSection">
                <div id="categoryArchiveTableContent">
                    <?php
                    $catArchiveSql = "SELECT archived_id, id AS category_id, name AS category_name, date
                    FROM categories_archive
                    ORDER BY date DESC";
                    $catArchiveResult = @$conn->query($catArchiveSql);
                    if ($catArchiveResult && $catArchiveResult->num_rows > 0) {
                        echo "<table border = 1>";
                        echo "<tr>
                            <th>Archive ID</th>
                            <th>Category ID</th>
                            <th>Category Name</th>
                            <th>Archived At</th>
                            <th>Action</th>
                        </tr>";
                        while ($row = $catArchiveResult->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['archived_id'] . "</td>";
                            echo "<td>" . $row['category_id'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                            echo "<td>" . $row['date'] . "</td>";
                            echo "<td><button type=\"button\" class=\"table-action-button restore-category-archive-button\" data-archive-id=\"" . $row['archived_id'] . "\">Restore</button></td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<div class=\"empty-state\">No archived categories found.</div>";
                    }
                    ?>
                </div>
            </section>

        </main>
    </div>

    <!-- Product hidden forms and modals -->
    <form id="productDeleteForm" action="./assets/removeProduct.php" method="POST" style="display:none;">
        <div id="productDeleteInputs"></div>
    </form>
    
    <!-- Stock Modal -->
    <div id="stockModal" class="stock-modal">
        <div class="stock-modal-content">
            <span class="close-btn">&times;</span>
            <h2>Add Stocks</h2>
            <form id="addStockForm" action="../pages/assets/addStock.php" method="POST">
                <label for="productId">Product ID</label>
                <input type="text" name="id" id="productId" placeholder="Enter product ID">
                <label for="stock_quantity_add">Stock Quantity</label>
                <input type="number" name="stock_quantity" id="stock_quantity_add" required placeholder="Enter quantity">
                <button id="saveStockButton" class="btn btn-primary" type="submit">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Product add modal -->
    <div id="productModal" class="product-modal">
        <div class="product-modal-content">
            <span class="close-btn">&times;</span>
            <h2>Add Products</h2>
            <form id="addProductForm" action="../pages/assets/addProduct.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="id">
                <label for="name">Product Name</label>
                <input type="text" name="name" id="name" required placeholder="Name">
                <label for="price">Price</label>
                <input type="number" name="price" id="price" required placeholder="Price">
                <label for="stock_quantity">Stock Quantity</label>
                <input type="number" name="stock_quantity" id="stock_quantity" required placeholder="Stock Quantity">
                <label for="product_image">Product Image</label>
                <input type="file" name="product_image" id="product_image" accept="image/png, image/jpeg, image/gif, image/webp" required>
                <label for="category_id">Category</label>
                <select name="category_id" id="category_id" required>
                    <option value="" disabled selected>Select category</option>
                    <?php
                    $result = $conn->query("SELECT id, name FROM categories");
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                        }
                    }
                    ?>
                </select>
                <button id="saveProductButton" class="btn btn-primary" type="submit">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Product edit modal -->
    <div id="editModal" class="edit-modal">
        <div class="edit-modal-content">
            <span class="close-btn">&times;</span>
            <h2>Edit Products</h2>
            <form id="editProductForm" action="./assets/editProduct.php" method="POST" enctype="multipart/form-data">
                <div id="editProductIds"></div>
                <label for="editProductName">Product Name</label>
                <input type="text" name="name" id="editProductName" placeholder="Leave blank to keep existing">
                <label for="editProductPrice">Price</label>
                <input type="number" name="price" id="editProductPrice" placeholder="Leave blank to keep existing">
                <label for="editProductStock">Stock Quantity</label>
                <input type="number" name="stock_quantity" id="editProductStock" placeholder="Leave blank to keep existing">
                <label for="editProductCategory">Category</label>
                <select name="category_id" id="editProductCategory">
                    <option value="" selected>Keep current category</option>
                    <?php
                    $result = $conn->query("SELECT id, name FROM categories");
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                        }
                    }
                    ?>
                </select>
                <label for="editProductStatus">Status</label>
                <select name="prodStatus" id="editProductStatus">
                    <option value="" selected>Keep current status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
                <label for="editProductImage">Product Image</label>
                <input type="file" name="product_image" id="editProductImage" accept="image/png, image/jpeg, image/gif, image/webp">
                <button id="saveEditButton" class="btn btn-primary" type="submit">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Category Modals -->
    <div id="categoryModal" class="product-modal">
        <div class="product-modal-content">
            <span class="close-btn close-cat-btn">&times;</span>
            <h2>Add Category</h2>
            <form id="addCategoryForm" action="../pages/assets/addCategory.php" method="POST">
                <label for="catName">Category Name</label>
                <input type="text" name="name" id="catName" required placeholder="Name">
                <button id="saveCategoryButton" class="btn btn-primary" type="submit">Save Changes</button>
            </form>
        </div>
    </div>

    <div id="editCategoryModal" class="edit-modal">
        <div class="edit-modal-content">
            <span class="close-btn close-cat-btn">&times;</span>
            <h2>Edit Category</h2>
            <form id="editCategoryForm" action="./assets/editCategory.php" method="POST">
                <div id="editCategoryIds"></div>
                <label for="editCatName">Category Name</label>
                <input type="text" name="name" id="editCatName" required placeholder="Enter new name">
                <button id="saveEditCatButton" class="btn btn-primary" type="submit">Save Changes</button>
            </form>
        </div>
    </div>

    <script src="../../jquery-4.0.0.min.js"></script>
    <script src="./script/stockscript.js"></script>
    <script src="../../lowStockAlert.js"></script>
</body>
</html>