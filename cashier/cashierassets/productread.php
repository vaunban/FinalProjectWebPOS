<?php
include("../connect.php");

$sql = "SELECT * FROM products";
$result = $conn->query($sql);

while($row = $result->fetch_assoc()){

    echo "
    <div class='product-card'>

        <div class='product-name'>
            {$row['name']}
        </div>

        <div class='product-image'>
            <img src='cashierassets/images/default.png' alt='product'>
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