<?php
    $servername = "localhost ";
    $username = "root";
    $password = "";
    $dbname = "posddatabase";

    $conn = new mysqli($servername,$username,$password,$dbname);

    if($conn->connect_error){
        die("Error!".$conn->connect_error);
    }
?>