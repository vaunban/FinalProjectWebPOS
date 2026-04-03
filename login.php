<?php
    session_start();
    include("connect.php");
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT username,id,role, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        $role = $row['role'];
        $pass = $row['password'];
        $id = $row['id'];
        $user = $row['username'];

        if($password === $pass){
            $_SESSION['id'] = $id;
            $_SESSION['username'] = $user;
            $_SESSION['role'] = $role;

            if($role == 'admin'){
                header("Location: admin/admin.php");
                exit();
            }
            else if($role == 'cashier'){
                header("Location: cashier/cashier.php");
                exit();
            }
            else{
                echo "Role not Found";
            }
        }
        else{
            header("Location: index.php?error=wrong_password");
            exit();
        }
    }
    else{
        header("Location: index.php?error=user_not_found");
        exit();
    }
    $stmt->close();
?>