<?php
session_start();
include(__DIR__ . "/../../config/connect.php");

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if(empty($username) || empty($password) || empty($role)) {
        echo "All fields are required.";
        exit();
    }

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
    header("Location: ../controllers/accounts.php");
    exit();
}
?>