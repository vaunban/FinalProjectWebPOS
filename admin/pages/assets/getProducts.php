<?php
include __DIR__ . '/../../../connect.php';

header('Content-Type: application/json');

// Determine whether to return inventory rows or archived rows.
// The front-end switches between these views using AJAX tab controls.
$view = isset($_GET['view']) && $_GET['view'] === 'archive' ? 'archive' : 'inventory';

// Only allow the fields we know how to sort securely.
$allowedSortFields = ['category', 'price', 'quantity', 'status', 'name'];
$sortField = isset($_GET['sortField']) && in_array($_GET['sortField'], $allowedSortFields, true) ? $_GET['sortField'] : '';
$sortDirection = isset($_GET['sortDirection']) && strtolower($_GET['sortDirection']) === 'desc' ? 'DESC' : 'ASC';

// Map selected sort options to SQL ordering clauses.
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

// Use output buffering so we can collect generated HTML and return it as JSON.
ob_start();

if ($view === 'archive') {
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

    // Build archive SQL and apply requested sort ordering.
    // The archive view includes a restore button in the action column.
    $archiveSql = "SELECT pa.archived_id, pa.id AS product_id, pa.name AS product_name, pa.price, pa.stock_quantity, pa.category_id, pa.prodStatus, pa.archived_at, pa.reason, c.name AS category_name
        FROM products_archive pa
        LEFT JOIN categories c ON pa.category_id = c.id" . $archiveSortSql;
    $archiveResult = $conn->query($archiveSql);

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
            . "<th>Reason</th>"
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
            echo "<td>" . htmlspecialchars($row['reason']) . "</td>";
            echo "<td><button type=\"button\" class=\"table-action-button restore-archive-button\" data-archive-id=\"" . $row['archived_id'] . "\">Restore</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class=\"empty-state\">No archived products found.</div>";
    }
} else {
    // Build inventory SQL and apply requested sort ordering.
    $sql = "SELECT p.name AS product_name,p.id,p.price,p.stock_quantity,p.prodStatus,p.category_id,c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id" . $sortSql;
    $result = $conn->query($sql);

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

$html = ob_get_clean();

echo json_encode(['success' => true, 'html' => $html]);
