<?php
session_start();
include(__DIR__ . "/../../config/connect.php");

if(isset($_GET['id'])){
    $user_id = $_GET['id'];

    // Fetch the user data first
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0){
        $_SESSION['error'] = "User not found.";
        header("Location: ../controllers/accounts.php");
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Insert into accounts_archived
    $stmt = $conn->prepare("INSERT INTO accounts_archive (id, username, password, role, archived_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $user['id'], $user['username'], $user['password'], $user['role']);
    
    if($stmt->execute()){
        $stmt->close();

        // Delete from users table
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if($stmt->execute()){
            $_SESSION['success'] = "User archived successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete user after archiving.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Failed to archive user.";
    }

    $conn->close();
    header("Location: ../controllers/accounts.php");
    exit();

} else {
    $_SESSION['error'] = "Invalid archive request.";
    header("Location: ../controllers/accounts.php");        
    exit();
}