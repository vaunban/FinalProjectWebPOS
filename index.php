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

    <div class = "main">
    <h1 style="font-style: italic; color: brown;"> Login! </h1>

    <form action="login.php" method="post">
        <div class = "user">
        <label for = "username" style="text-align: left;" > Username </label>
        <br>
        <input type="text" id="username" name="username" placeholder="Username" required><br>
        </div>

        <div class = "pass">
        <label for = "password" style="text-align: left;"> Password </label>
        <br>
        <input type="password" id="password" name="password" placeholder="Password" required><br>
        </div>

        <div class = "rem">
            <input type = "checkbox" id="checkbox" name="checkbox">
            <label for = "checkbox"> Remember Me</label>
        </div>

        <div>
        <br>
        <input type="submit" value="Login">
        </div>

    </form>
    </div>
</body>
</html>