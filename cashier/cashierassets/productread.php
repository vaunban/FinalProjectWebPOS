<?php
include("../connect.php");

$sql = "SELECT * FROM products";
$result = $conn->query($sql);

while($row = $result->fetch_assoc()){

    $image = !empty($row['icon_filename']) ? $row['icon_filename'] : 'default.png';

    echo "
    <div class='product-card'>

        <div class='product-name'>
            {$row['name']}
        </div>

        <div class='product-image'>
            <img src='cashierassets/images/{$image}' alt='product'>
        </div>

        <div class='product-price'>
            ₱{$row['price']}
        </div>

        <button class='add-to-cart'>
            Add to Cart
        </button>

    </div>
    ";
}
?>