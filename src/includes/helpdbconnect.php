<?php
$database = mysqli_connect(getenv("HELPMYSQL_HOST"), getenv("HELPMYSQL_USER"), getenv("HELPMYSQL_PASSWORD"), getenv("HELPMYSQL_DATABASE"));
if (!$database) {
    die('Could not connect to MySQL: ' . mysqli_connect_error());
}
