<?php
/**
 * categread.php
 * Fetches all product categories from the database and renders them
 * as clickable category filter buttons in the cashier product panel.
 * Each button uses a lowercase data-category attribute for JavaScript filtering
 * and a properly capitalized label for display.
 *
 * Included by: cashier.php (inside the category-buttons div)
 */

// Include the database connection
include("../connect.php");

// Query all available categories from the categories table
$sql = "SELECT * FROM categories";
$result = $conn->query($sql);

// Loop through each category and output a filter button
while($row = $result->fetch_assoc()){
    // Use lowercase for the data attribute (used by JS filtering logic)
    $category = strtolower($row['name']);

    // Capitalize the first letter for the visible button label
    $label = ucfirst($row['name']);

    echo "<button class='category-btn' data-category='$category'>
            $label
          </button>";
}
?>