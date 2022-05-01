<?php
    // Enable us to use Headers
    ob_start();
    // Set sessions
    if(!isset($_SESSION)) {
        session_start();
    }
    $hostname = "localhost";
    $username = "admin";
    $password = "espiftw1";
    $dbname = "helpprovoedu";

    $link = mysqli_connect($hostname, $username, $password, $dbname) or die("Database connection not established.");

/*
    if($link) {
      echo 'works';
    } else {
      echo 'borked';
    }
*/
?>
