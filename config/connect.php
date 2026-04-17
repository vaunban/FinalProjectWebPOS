<?php
$servername = "localhost";
$username = "root";
$password = "admin123";
$dbname = "db45126";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error!" . $conn->connect_error);
}
?>