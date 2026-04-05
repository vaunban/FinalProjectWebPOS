<?php
session_start();
include("../../../connect.php");

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $user_id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $role = $_POST['role'];

    if(empty($username) || empty($role)) {
        echo "All fields are required.";
        exit();
    }

    $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
    $stmt->bind_param("ssi", $username, $role, $user_id);   

    if ($stmt->execute()) {
        $_SESSION['success'] = "User updated successfully!";
        header("Location: ../accounts.php");
        exit();
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
        header("Location: ../accounts.php");
        exit();
    }
    
    $stmt->close();
    $conn->close();
} else {
    header("Location: ../accounts.php");
    exit();
}
?>