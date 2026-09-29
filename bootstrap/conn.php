<?php
    $hostname = $_ENV['DB_HOST'];
    $username = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];
    $db_name = $_ENV['DB_NAME'];


    //connection to the database
    $conn = new mysqli($hostname, $username, $password, $db_name);
    /* check connection */
    if ($conn->connect_errno) {
        printf("Connect failed: %s\n", $conn->connect_error);
        exit();
    }    
    
?>
