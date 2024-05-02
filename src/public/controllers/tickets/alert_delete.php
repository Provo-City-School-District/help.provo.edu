<?php
require_once('init.php');
require_once("block_file.php");
require_once('helpdbconnect.php');

if (!isset($_GET['id'])) {
	log_app(LOG_ERR, "[alert_delete.php] id not set!");
	http_response_code(400);
	exit;
}

$id = $_GET['id'];
$username = $_SESSION["username"];

log_app(LOG_INFO, "[alert_delete.php] Deleting alert with id=$id");

$database->execute_query("DELETE FROM alerts WHERE id = ? AND LOWER(employee) = ?", [$id, strtolower($username)]);

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
