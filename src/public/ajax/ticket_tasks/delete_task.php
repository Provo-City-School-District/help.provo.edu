<?php
require("helpdbconnect.php");
require("block_file.php");
require("functions.php");
require("ticket_utils.php");

$post_filtered = filter_input_array(INPUT_POST, [
    "task_id" => FILTER_VALIDATE_INT
]);

$task_id = $post_filtered["task_id"];
$username = $_SESSION["username"];

if (!user_is_tech($username)) {
	log_app(LOG_INFO, "[delete_task.php] User is not a tech. Ignoring ajax request...");
	http_response_code(401);
	exit;
}

log_app(LOG_INFO, "[delete_task.php] Deleting task id=$task_id");

$update_status_res = $database->execute_query("DELETE FROM help.ticket_tasks WHERE id = ?", [$task_id]);

if (!$update_status_res) {
    log_app(LOG_ERR, "[delete_task.php] Failed to execute query");
    http_response_code(500);
} else {
    log_app(LOG_INFO, "[delete_task.php] Successfully deleted task id=$task_id");
    http_response_code(200);
}