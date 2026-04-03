<?php
/**
 * productread.php
 * Fetches active products from the database and renders them as product cards.
 * Supports optional filtering by category (via GET 'category') and
 * keyword search (via GET 'query'). Only products with active status
 * and stock greater than 0 are shown.
 *
 * Called via: AJAX (GET) from posscript.js → loadProducts()
 * Also included directly by: cashier.php for the initial page load
 */

// Include the database connection
include(__DIR__ . '/../../connect.php');

// Read category filter and search query from GET parameters, defaulting to 'all' and empty string
$category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Base SQL: select active products with stock, joined with their category name
$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.prodStatus = 'active' AND p.stock_quantity > 0";
$params = [];
$types = '';

// Append category filter if a specific category (not 'all') is selected
if ($category !== '' && strtolower($category) !== 'all') {
    $sql .= " AND LOWER(c.name) = ?";
    $params[] = strtolower($category);
    $types .= 's';
}

// Append search keyword filter if a query string is provided
if ($query !== '') {
    $sql .= " AND LOWER(p.name) LIKE ?";
    $params[] = '%' . strtolower($query) . '%';
    $types .= 's';
}

// Prepare the SQL statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    // Output an error message if the statement could not be prepared
    echo '<div class="product-message">Unable to load products.</div>';
    exit;
}

// Bind parameters dynamically if any filters were applied
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

// Execute the query and get results
$stmt->execute();
$result = $stmt->get_result();

// If no matching products are found, show a friendly message
if ($result->num_rows === 0) {
    echo '<div class="product-message">No products found.</div>';
    exit;
}

// Loop through each product row and render a product card
while ($row = $result->fetch_assoc()) {
    // Use the product's image filename, or fall back to default.png
    $image = !empty($row['icon_filename']) ? $row['icon_filename'] : 'default.png';

    // Normalize the category name for use as a data attribute
    $categoryKey = strtolower(trim($row['category_name'] ?? ''));

    // Format the price to 2 decimal places
    $price = number_format((float)$row['price'], 2, '.', '');

    // Output the product card HTML with data attributes for the JS cart logic
    echo "
    <div class='product-card' data-category='{$categoryKey}'>

        <div class='product-name'>
            " . htmlspecialchars($row['name'], ENT_QUOTES) . "
        </div>

        <div class='product-image'>
            <img src='cashierassets/images/{$image}' alt='product'>
        </div>

        <div class='product-price'>
            ₱{$price}
        </div>

        <button class='add-to-cart'
                data-product-id='" . htmlspecialchars($row['id'], ENT_QUOTES) . "'
                data-product-name='" . htmlspecialchars($row['name'], ENT_QUOTES) . "'
                data-product-price='{$price}'
                data-product-category='{$categoryKey}'
                data-product-stock='" . htmlspecialchars($row['stock_quantity'], ENT_QUOTES) . "'>
            Add to Cart
        </button>

    </div>
    ";
}

// Close the prepared statement to free resources
$stmt->close();
?>