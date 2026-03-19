<?php
    include("connect.php");
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if($result->num_rows>0){
        $row = $result->fetch_assoc();
        $role = $row['role'];

        if($role == 'admin'){
            Header("Location: admin.php");

        }
        else if($role == 'cashier'){
            Header("Location: cashier.php");
        }
        else{
            echo"Role not Found";
        }
        
    }else{
        echo"Login Failed";
    }
?>