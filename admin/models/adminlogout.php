<?php
/**
 * adminlogout.php
 * Handles admin logout by clearing cookies and destroying the session.
 * Redirects the user back to the main login page (index.php).
 */

session_start();

// Clear "Remember Me" cookies by setting them to expire in the past
setcookie('username','',time() - 3600, "/");
setcookie('password','',time() - 3600, "/");

// Remove all session variables
session_unset();

// Destroy the session completely
session_destroy();

// Redirect to the login page
header("Location: ../../index.php");
exit();
?>