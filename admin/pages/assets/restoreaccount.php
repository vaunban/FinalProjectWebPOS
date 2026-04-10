<?php
session_start();
include (__DIR__ . '/../../../connect.php');

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Get user from archive
    $stmt = $conn->prepare("SELECT * FROM accounts_archive WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        // Insert back into accounts
        $insert = $conn->prepare("INSERT INTO users (id, username, password ,role) VALUES (?, ?, ?, ?)");
        $insert->bind_param("isss", $row['id'], $row['username'], $row['password'], $row['role']);

        if ($insert->execute()) {

            // Delete from archive
            $delete = $conn->prepare("DELETE FROM accounts_archive WHERE id = ?");
            $delete->bind_param("i", $user_id);
            $delete->execute();

            $_SESSION['success'] = "User restored successfully!";
        } else {
            $_SESSION['error'] = "Failed to restore user.";
        }

        $conn->close();
        header("Location: archived_accounts.php");
        exit();

    } else {
        $_SESSION['error'] = "User not found in archive.";
        header("Location: archived_accounts.php");
        exit();
    }

}