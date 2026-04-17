<?php
/**
 * accountdelete.php
 * Permanently deletes an archived user account from the accounts_archive table.
 * Called from the Archived Accounts page when admin confirms deletion.
 * Redirects back to archived_accounts.php with a success or error message.
 */

session_start();
include(__DIR__ . '/../../config/connect.php');

if(isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Delete the user from the archive table
    $stmt = $conn->prepare("DELETE FROM accounts_archive WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if($stmt->execute()){
            $_SESSION['success'] = "User deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete user.";
        }

        $conn->close();
        header("Location: ../controllers/archived_accounts.php");
        exit();
} else {
    $_SESSION['error'] = "Failed to delete user.";

    $conn->close();
    header("Location: ../controllers/archived_accounts.php");
    exit();
}
?>