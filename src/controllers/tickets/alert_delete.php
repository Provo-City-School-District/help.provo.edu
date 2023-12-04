<?php

require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');

$id = $_GET['id'];
$query = "DELETE FROM alerts WHERE id = $id";
mysqli_query($database, $query);
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
