<?php
/**
 * restoreaccount.php
 * Restores an archived user account back to the active 'users' table.
 * Copies the user data from 'accounts_archive' back to 'users',
 * then deletes the archive record.
 * Called when admin clicks "Restore" on the Archived Accounts page.
 */

session_start();
include(__DIR__ . '/../../config/connect.php');

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Step 1: Get the user data from the archive table
    $stmt = $conn->prepare("SELECT * FROM accounts_archive WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        // Step 2: Insert the user back into the active users table
        $insert = $conn->prepare("INSERT INTO users (id, username, password ,role) VALUES (?, ?, ?, ?)");
        $insert->bind_param("isss", $row['id'], $row['username'], $row['password'], $row['role']);

        if ($insert->execute()) {

            // Step 3: Delete the record from the archive table
            $delete = $conn->prepare("DELETE FROM accounts_archive WHERE id = ?");
            $delete->bind_param("i", $user_id);
            $delete->execute();

            $_SESSION['success'] = "User restored successfully!";
        } else {
            $_SESSION['error'] = "Failed to restore user.";
        }

        $conn->close();
        header("Location: ../controllers/archived_accounts.php");
        exit();

    } else {
        $_SESSION['error'] = "User not found in archive.";
        header("Location: ../controllers/archived_accounts.php");
        exit();
    }

}