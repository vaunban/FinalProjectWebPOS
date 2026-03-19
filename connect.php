<?php
    $servername = "db45126.public.databaseasp.net";
    $username = "db45126";
    $password = "admin123";
    $dbname = "db45126";

    $conn = new mysqli($servername,$username,$password,$dbname);

    if($conn->connect_error){
        die("Error!".$conn->connect_error);
    }
    else{
        echo"Connected Successfully";
    }
?>