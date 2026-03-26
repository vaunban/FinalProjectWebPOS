<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/cashierstyle.css">
</head>
<body>

    <nav class="navbar">
        <div class="logo">MERKADO</div>
        <div class="navbaritems">
            <a href="../index.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <main class="content">
        <section class="left-panel">
            <form class="search-form" action="#" method="GET">
                <input type="text" name="query" placeholder="Search items..." aria-label="Search items" autocomplete="off">
                <button type="submit">Search</button>
            </form>
            <p>Panel for items, cart, or controls.</p>
        </section>
        <section class="right-panel">
            <h2>Current Order</h2>
            <p>Panel for totals, receipt, or shortcuts.</p>
        </section>
    </main>
</body>
</html>