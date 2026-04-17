<?php
/**
 * connect.php
 * Database connection configuration for the MERKADO POS system.
 * Creates a MySQLi connection object ($conn) used by all models and controllers.
 * Update the credentials below to match your database setup.
 */

// Database credentials
$servername = "localhost";
$username = "root";
$password = "admin123";
$dbname = "db45126";

// Create connection to the MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection failed
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>