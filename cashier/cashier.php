<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="cashiercss/cashierstyle.css">
    <link rel="stylesheet" href="../jquery-4.0.0.min.js">
</head>
<body>

    <nav class="navbar">
        <div class="logo">MERKADO</div>
        <div class="navbaritems">
            <a href="cashierassets/cashierlogout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <main class="content">
        <section class="left-panel">
            <form class="search-form" action="#" method="GET">
                <input type="text" name="query" placeholder="Search items..." aria-label="Search items" autocomplete="off">
                <button type="submit">Search</button>
            </form>
            
                    <div class="category-section">
                        <div class="category-buttons">
                                <button class="category-btn" data-category="meat">Meat</button>
                                <button class="category-btn" data-category="beverage">Beverage</button>
                                <button class="category-btn" data-category="vegetables">Vegetables</button>
                                <button class="category-btn" data-category="fruits">Fruits</button>
                                <button class="category-btn" data-category="dairy">Dairy</button>
                                <button class="category-btn" data-category="snacks">Snacks</button>
                        </div>
                    </div>

            <p>Panel for items, cart, or controls.</p>
        </section>
        <section class="right-panel">
            <h2>Current Order</h2>
            <p>Panel for totals, receipt, or shortcuts.</p>
        </section>
    </main>
</body>
</html>