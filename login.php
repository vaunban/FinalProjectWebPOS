<?php
    include("connect.php");
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT role, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        $role = $row['role'];
        $pass = $row['password'];

        if($password === $pass){
            if($role == 'admin'){
                Header("Location: admin/admin.php");
            }
            else if($role == 'cashier'){
                Header("Location: cashier/cashier.php");
            }
            else{
                echo"Role not Found";
            }
        }
        else{
            echo"Wrong Password";
        }
    }
    else{
        echo"Login Failed";
    }
    $stmt->close();
?>