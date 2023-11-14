<?php
require_once("helpdbconnect.php");

function user_exists_locally(string $email)
{
    global $database;

    $check_query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($database, $check_query);

    // If a row is returned, the user exists
    return mysqli_num_rows($result) > 0;
}