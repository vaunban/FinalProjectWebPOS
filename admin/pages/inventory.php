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
    <title>Document</title>
    <link rel="stylesheet" href="./css/stockstyle.css">

</head>
<body>
    <!-- Main page wrapper containing sidebar and content area -->
    <div class="page-container">
        <!-- Persistent admin navigation sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><a href="../admin.php">MERKADO</a></h2>
            </div>
            <ul class="sidebar-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="inventory.php">Inventory</a></li>
                <li><a href="customers.php">Customers</a></li>
                <li><a href="accounts.php">Accounts</a></li>
                <li><a href="assets/adminlogout.php">Log Out</a></li>
            </ul>
        </aside>

        <main class="content">
            <!-- Header section with page title and top action buttons -->
            <header class="page-top">
                <div class="page-title-group">
                    <h1 id="pageTitle">Inventory</h1>
                    <p id="pageSubtitle">Manage your product stock, categories, and availability in one place.</p>
                </div>
                <!-- Inventory / Archive tabs let the admin switch between active stock and archived products -->
                <div class="tab-buttons">
                    <button type="button" class="tab-button active" id="inventoryTab">Inventory</button>
                    <button type="button" class="tab-button" id="archiveTab">Archive</button>
                </div>
                <!-- Sort controls are shown only on the Inventory tab and use AJAX to refresh the table -->
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
                <div class="action-buttons" id="inventoryActions">
                    <button type="button" id="openStockModalButton" class="btn btn-secondary">Add Stock</button>
                    <button type="button" id="openProductModalButton" class="btn btn-primary">Add Product</button>
                    <button type="button" id="bulkEditButton" class="btn btn-secondary">Bulk Edit</button>
                    <button type="button" id="bulkDeleteButton" class="btn btn-danger">Bulk Delete</button>
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
                    <h2>Stock Overview</h2>
                    <p>Review all product quantities and statuses. Use the buttons above to update stock or add new items.</p>
                </article>
            </section>

            <section class="inventory-table" id="inventorySection">
                <div id="inventoryTableContent">
                    <!-- Inventory rows are loaded server-side on first page load and replaced via AJAX when sorting or deleting products. -->
                    <?php
                    // Connect to the database and load current product inventory with category names.
                    include (__DIR__ . '/../..//connect.php');
                    $sql = "SELECT p.name AS product_name,p.id,p.price,p.stock_quantity,p.prodStatus,p.category_id,c.name AS category_name
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

            <section class="inventory-table hidden" id="archiveSection">
                <div id="archiveTableContent">
                    <!-- Archived rows are initially rendered server-side for the archive tab and can be restored with the restore button. -->
                    <?php
                    $archiveSql = "SELECT pa.archived_id, pa.id AS product_id, pa.name AS product_name, pa.price, pa.stock_quantity, pa.category_id, pa.prodStatus, pa.archived_at, pa.reason, c.name AS category_name
                    FROM products_archive pa
                    LEFT JOIN categories c ON pa.category_id = c.id
                    ORDER BY pa.archived_at DESC";
                    $archiveResult = $conn->query($archiveSql);
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
                            <th>Reason</th>
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
                            echo "<td>" . $row['reason'] . "</td>";
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
        </main>
    </div>

    <!-- Hidden delete form used by bulk/single delete actions via JavaScript -->
    <form id="productDeleteForm" action="./assets/removeProduct.php" method="POST" style="display:none;">
        <div id="productDeleteInputs"></div>
    </form>

    <!-- Modal to add or update stock for an existing product -->
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

    <!-- Modal to add a new product, including image upload and category selection -->
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
                    // Load category names for the Add Product dropdown.
                    include (__DIR__ . '/../..//connect.php');
                    $sql = "SELECT id, name FROM categories";
                    $result = $conn->query($sql);
                    $categories = [];
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $categories[] = $row;
                        }

                        foreach ($categories as $category) {
                            echo '<option value="' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</option>';
                        }
                    }
                    ?>
                </select>
                <button id="saveProductButton" class="btn btn-primary" type="submit">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Modal to edit one or more products; form fields are prefilled when editing a single product -->
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
                    include (__DIR__ . '/../..//connect.php');
                    $sql = "SELECT id, name FROM categories";
                    $result = $conn->query($sql);
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

    <!-- Load jQuery first, then the custom inventory script for modal and AJAX behavior. -->
    <script src="../../jquery-4.0.0.min.js"></script>
    <script src="./script/stockscript.js"></script>
</body>
</html>