<?php
$user_db = mysqli_connect(getenv("USERDB_HOST"), getenv("USERDB_USER"), getenv("USERDB_PASSWORD"), getenv("USERDB_PASSWORD")) or die('failed to connect');
?>