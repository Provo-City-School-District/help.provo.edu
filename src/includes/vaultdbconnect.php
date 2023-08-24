<?php
$user_db = mysqli_connect(getenv("VAULT_READ_HOST"), getenv("VAULT_READ_USER"), getenv("VAULT_READ_PASSWORD"), getenv("VAULT_READ_DATABASE")) or die('failed to connect');
?>