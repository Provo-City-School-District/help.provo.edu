<?php
$database = mysqli_connect(getenv("HELPMYSQL_HOST"), getenv("HELPMYSQL_USER"), getenv("HELPMYSQL_PASSWORD"), getenv("HELPMYSQL_DATABASE")) or die('failed to connect');
?>