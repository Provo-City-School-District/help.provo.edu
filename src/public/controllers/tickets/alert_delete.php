<?php

require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');

$id = $_GET['id'];
$database->execute_query("DELETE FROM alerts WHERE id = ?", [$id]);
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
