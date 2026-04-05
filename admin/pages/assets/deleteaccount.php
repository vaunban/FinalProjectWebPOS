<?php
session_start();
include("../../../connect.php");

if(isset($_GET['id'])){
    $user_id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if($stmt->execute()){
        $_SESSION['success'] = "User deleted successfully!";
        header("Location: ../accounts.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to delete user";
    }
    $stmt->close();
    $conn->close();

    header("Location: ../accounts.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid delete request.";
    header("Location: ../accounts.php");        
    exit();
 }


?>