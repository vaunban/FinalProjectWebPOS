<?php
/**
 * accounts_archive.php
 * Archives (soft-deletes) a user account.
 * Copies the user data from the 'users' table to the 'accounts_archive' table,
 * then deletes the original user record.
 * Called when an admin clicks "Archive" on the accounts page.
 */

session_start();
include(__DIR__ . "/../../config/connect.php");

if(isset($_GET['id'])){
    $user_id = $_GET['id'];

    // Step 1: Fetch the user data from the users table
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if the user exists
    if($result->num_rows === 0){
        $_SESSION['error'] = "User not found.";
        header("Location: ../controllers/accounts.php");
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Step 2: Insert the user data into the archive table
    $stmt = $conn->prepare("INSERT INTO accounts_archive (id, username, password, role, archived_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $user['id'], $user['username'], $user['password'], $user['role']);
    
    if($stmt->execute()){
        $stmt->close();

        // Step 3: Delete the user from the active users table
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