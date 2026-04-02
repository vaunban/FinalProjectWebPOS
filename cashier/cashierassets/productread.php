<?php
include(__DIR__ . '/../../connect.php');

$category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.prodStatus = 'active'";
$params = [];
$types = '';

if ($category !== '' && strtolower($category) !== 'all') {
    $sql .= " AND LOWER(c.name) = ?";
    $params[] = strtolower($category);
    $types .= 's';
}

if ($query !== '') {
    $sql .= " AND LOWER(p.name) LIKE ?";
    $params[] = '%' . strtolower($query) . '%';
    $types .= 's';
}

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo '<div class="product-message">Unable to load products.</div>';
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="product-message">No products found.</div>';
    exit;
}

while ($row = $result->fetch_assoc()) {
    $image = !empty($row['icon_filename']) ? $row['icon_filename'] : 'default.png';
    $categoryKey = strtolower(trim($row['category_name'] ?? ''));
    $price = number_format((float)$row['price'], 2, '.', '');

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
                data-product-category='{$categoryKey}'>
            Add to Cart
        </button>

    </div>
    ";
}

$stmt->close();
?>