<?php
// Connect to the database
$swdb = mysqli_connect(getenv("SWHELPDESKHOST"), getenv("SWHELPDESKUSER"), getenv("SWHELPDESKPASSWORD"), getenv("SWHELPDESKDATABASE"));

// Check if the connection was successful
if (!$swdb) {
    die('Connection failed: ' . mysqli_connect_error());
}
?>