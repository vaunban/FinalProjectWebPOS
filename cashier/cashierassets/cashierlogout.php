<?php
/**
 * cashierlogout.php
 * Handles cashier logout by clearing all session data and destroying the session,
 * then redirects the user back to the main login page (index.php).
 */

session_start();

// Remove all session variables
session_unset();

// Destroy the session completely
session_destroy();

// Redirect to the login page after logout
header("Location: ../../index.php");
exit();
?>