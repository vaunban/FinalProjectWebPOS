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
    <script src="../jquery-4.0.0.min.js"></script>
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
                                <?php
                                    include("cashierassets/categread.php");
                                ?>
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