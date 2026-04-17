<?php
$servername = "localhost";
$username = "root";
$password = "admin123";
$dbname = "db45126";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>