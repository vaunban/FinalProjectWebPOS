<?php
/**
 * login.php
 * Handles the login form submission from index.php.
 * Verifies the username and password against the database,
 * sets session variables on success, and redirects users
 * to the correct page based on their role (admin or cashier).
 */

    // Start the session to store login info
    session_start();

    // Connect to the database
    include("config/connect.php");

    // Get the username and password from the login form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Look up the user in the database using a prepared statement (prevents SQL injection)
    $stmt = $conn->prepare("SELECT username,id,role, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the user exists
    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        $role = $row['role'];
        $pass = $row['password'];
        $id = $row['id'];
        $user = $row['username'];

        // If "Remember Me" was checked, save cookies for 7 days
        if (isset($_POST['checkbox']))
            {
                setcookie('username', $user, time() + 3600*24*7, "/");
                setcookie('password', $pass, time() + 3600*24*7, "/");
            }

        // Verify the password matches
        if($password === $pass){
            // Store user info in the session
            $_SESSION['id'] = $id;
            $_SESSION['username'] = $user;
            $_SESSION['role'] = $role;

            // Redirect based on user role
            if($role == 'admin'){
                header("Location: admin/controllers/admin.php");
                exit();
            }
            else if($role == 'cashier'){
                header("Location: cashier/controllers/cashier.php");
                exit();
            }
            else{
                echo "Role not Found";
            }
        }
        else{
            // Wrong password — redirect back with error
            header("Location: index.php?error=wrong_password");
            exit();
        }
    }
    else{
        // Username not found — redirect back with error
        header("Location: index.php?error=user_not_found");
        exit();
    }
    $stmt->close();
?>