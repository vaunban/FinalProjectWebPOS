<?php
session_start();
include (__DIR__ . '/../../../connect.php');

if(isset($_GET['id'])) {
    $user_id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM accounts_archive WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if($stmt->execute()){
            $_SESSION['success'] = "User deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete user.";
        }

        $conn->close();
        header("Location: archived_accounts.php");
        exit();
} else {
    $_SESSION['error'] = "Failed to delete user.";

    $conn->close();
    header("Location: archived_accounts.php");
    exit();
}
?>