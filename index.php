<?php

    $username_cookie = "";
    $password_cookie = "";

    if (isset($_COOKIE['username']))
        {
            $username_cookie = $_COOKIE['username'];
        }

    if (isset($_COOKIE['password']))
        {
            $password_cookie = $_COOKIE['password'];
        }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="jquery-4.0.0.min.js"></script>
    <link rel="stylesheet" href="stylesheet.css">

</head>
<body>
    <div class = "welc">
    <h4> welcome to </h4>
    <h2> merkado </h2>
    </div>

    <?php
    $error_message = '';
    if (!empty($_GET['error'])) {
        if ($_GET['error'] === 'wrong_password') {
            $error_message = 'Incorrect password. Please try again.';
        } elseif ($_GET['error'] === 'user_not_found') {
            $error_message = 'Username not found. Please check your username.';
        } else {
            $error_message = 'Login failed. Please try again.';
        }
    }
    ?>

    <div class = "main">
    <h1 style="font-style: italic; color: brown;"> Login! </h1>

    <?php if ($error_message): ?>
        <div style="color: red; margin-bottom: 8px; font-weight: bold;">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="post">
        <div class = "user">
        <label for = "username" style="text-align: left;" > Username </label>
        <br>
        <input type="text" id="username" name="username" placeholder="Username" value = "<?= htmlspecialchars($username_cookie)?>" required><br>
        </div>

        <div class = "pass">
        <label for = "password" style="text-align: left;"> Password </label>
        <br>
        <input type="password" id="password" name="password" placeholder="Password" value = "<?= htmlspecialchars($password_cookie)?>" required><br>
        </div>

            <input type = "checkbox" id="checkbox" name="checkbox">
            <label for = "checkbox"> Remember Me</label>

        <div>
        <br>
        <input type="submit" value="Login">
        </div>

    </form>
    </div>
</body>
</html>