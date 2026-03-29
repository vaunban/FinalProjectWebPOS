<?php
include("../connect.php");

$sql = "SELECT * FROM categories";
$result = $conn->query($sql);

while($row = $result->fetch_assoc()){
    $category = strtolower($row['name']);
    $label = ucfirst($row['name']);

    echo "<button class='category-btn' data-category='$category'>
            $label
          </button>";
}
?>