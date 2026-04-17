<?php
/**
 * accountinsert.php
 * Handles adding a new user account to the system.
 * Receives username, password, and role from the Add User form.
 * Inserts the new user into the 'users' table and redirects back to accounts page.
 */

session_start();
include(__DIR__ . "/../../config/connect.php");

// Only process POST requests
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Get form data
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    // Validate that all fields are filled
    if(empty($username) || empty($password) || empty($role)) {
        echo "All fields are required.";
        exit();
    }

    // Insert the new user into the database
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);   

    if ($stmt->execute()) {
        $_SESSION['success'] = "User added successfully!";
        header("Location: ../controllers/accounts.php");
        exit();
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
        header("Location: ../controllers/accounts.php");
        exit();
    }
    
    $stmt->close();
    $conn->close();
} else {
    // Redirect if accessed without POST
    header("Location: ../controllers/accounts.php");
    exit();
}
?>