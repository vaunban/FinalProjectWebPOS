<?php
/**
 * editaccount.php
 * Handles updating an existing user account.
 * Receives user_id, username, password, and role from the Edit User form.
 * Updates the user record in the 'users' table.
 */

session_start();
include(__DIR__ . "/../../config/connect.php");

// Only process POST requests
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Get form data
    $user_id = $_POST['user_id'];
    $username = ($_POST['username']);
    $password = ($_POST['password']);
    $role = $_POST['role'];

    // Validate that all fields are filled
    if(empty($username) || empty($role) || empty($password)) {
        echo "All fields are required.";
        exit();
    }

    // Update the user in the database
    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $password, $role, $user_id);   

    if ($stmt->execute()) {
        $_SESSION['success'] = "User updated successfully!";
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