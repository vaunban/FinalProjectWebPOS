<?php
/**
 * getProducts.php
 * Returns product data as JSON with HTML table markup.
 * Supports two views:
 *   - 'inventory' (default): active products with edit/delete buttons and sorting
 *   - 'archive': archived products with restore buttons
 * Called via AJAX from stockscript.js when switching tabs or applying sort.
 */

include __DIR__ . '/../../config/connect.php';

header('Content-Type: application/json');

// Determine which view to return (inventory or archive)
$view = isset($_GET['view']) && $_GET['view'] === 'archive' ? 'archive' : 'inventory';

// Only allow whitelisted sort fields to prevent SQL injection
$allowedSortFields = ['category', 'price', 'quantity', 'status', 'name'];
$sortField = isset($_GET['sortField']) && in_array($_GET['sortField'], $allowedSortFields, true) ? $_GET['sortField'] : '';
$sortDirection = isset($_GET['sortDirection']) && strtolower($_GET['sortDirection']) === 'desc' ? 'DESC' : 'ASC';

// Map sort field names to actual SQL column references
$sortSql = '';
if ($sortField === 'category') {
    $sortSql = " ORDER BY c.name {$sortDirection}";
} elseif ($sortField === 'price') {
    $sortSql = " ORDER BY p.price {$sortDirection}";
} elseif ($sortField === 'quantity') {
    $sortSql = " ORDER BY p.stock_quantity {$sortDirection}";
} elseif ($sortField === 'status') {
    $sortSql = " ORDER BY p.prodStatus {$sortDirection}";
} elseif ($sortField === 'name') {
    $sortSql = " ORDER BY p.name {$sortDirection}";
}

// Use output buffering to collect generated HTML
ob_start();

if ($view === 'archive') {
    // Build sort SQL for archive view (uses pa. prefix instead of p.)
    $archiveSortSql = '';
    if ($sortField === 'category') {
        $archiveSortSql = " ORDER BY c.name {$sortDirection}";
    } elseif ($sortField === 'price') {
        $archiveSortSql = " ORDER BY pa.price {$sortDirection}";
    } elseif ($sortField === 'quantity') {
        $archiveSortSql = " ORDER BY pa.stock_quantity {$sortDirection}";
    } elseif ($sortField === 'status') {
        $archiveSortSql = " ORDER BY pa.prodStatus {$sortDirection}";
    } elseif ($sortField === 'name') {
        $archiveSortSql = " ORDER BY pa.name {$sortDirection}";
    }

    // Query archived products
    $archiveSql = "SELECT pa.archived_id, pa.id AS product_id, pa.name AS product_name, pa.price, pa.stock_quantity, pa.category_id, pa.prodStatus, pa.archived_at, c.name AS category_name
        FROM products_archive pa
        LEFT JOIN categories c ON pa.category_id = c.id" . $archiveSortSql;
    $archiveResult = $conn->query($archiveSql);

    // Render archive table with restore buttons
    if ($archiveResult && $archiveResult->num_rows > 0) {
        echo "<table border=\"1\">";
        echo "<tr>"
            . "<th>Archive ID</th>"
            . "<th>Product ID</th>"
            . "<th>Product Name</th>"
            . "<th>Price</th>"
            . "<th>Quantity</th>"
            . "<th>Category</th>"
            . "<th>Status</th>"
            . "<th>Archived At</th>"

            . "<th>Action</th>"
            . "</tr>";
        while ($row = $archiveResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['archived_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['price']) . "</td>";
            echo "<td>" . htmlspecialchars($row['stock_quantity']) . "</td>";
            echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['prodStatus']) . "</td>";
            echo "<td>" . htmlspecialchars($row['archived_at']) . "</td>";

            echo "<td><button type=\"button\" class=\"table-action-button restore-archive-button\" data-archive-id=\"" . $row['archived_id'] . "\">Restore</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class=\"empty-state\">No archived products found.</div>";
    }
} else {
    // Query active products with category names
    $sql = "SELECT p.name AS product_name,p.id,p.price,p.stock_quantity,p.prodStatus,p.category_id,c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id" . $sortSql;
    $result = $conn->query($sql);

    // Render inventory table with edit/delete buttons
    if ($result && $result->num_rows > 0) {
        echo "<table border=\"1\">";
        echo "<tr>"
            . "<th class=\"select-column\"><input type=\"checkbox\" id=\"selectAll\"></th>"
            . "<th>ID</th>"
            . "<th>Product Name</th>"
            . "<th>Price</th>"
            . "<th>Quantity</th>"
            . "<th>Category</th>"
            . "<th>Status</th>"
            . "<th>Action</th>"
            . "</tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td class=\"select-column\"><input type=\"checkbox\" class=\"row-select\" data-id=\"" . $row['id'] . "\"></td>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['price']) . "</td>";
            echo "<td>" . htmlspecialchars($row['stock_quantity']) . "</td>";
            echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['prodStatus']) . "</td>";
            echo "<td><button type=\"button\" class=\"table-action-button product-edit-button\" data-id=\"" . $row['id'] . "\" data-name=\"" . htmlspecialchars($row['product_name'], ENT_QUOTES) . "\" data-price=\"" . $row['price'] . "\" data-stock=\"" . $row['stock_quantity'] . "\" data-category-id=\"" . $row['category_id'] . "\" data-status=\"" . $row['prodStatus'] . "\">Edit</button> ";
            echo "<button type=\"button\" class=\"table-action-button product-delete-button delete\" data-id=\"" . $row['id'] . "\">Delete</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class=\"empty-state\">No stock data found.</div>";
    }
}

// Capture the buffered HTML and return as JSON
$html = ob_get_clean();

echo json_encode(['success' => true, 'html' => $html]);
