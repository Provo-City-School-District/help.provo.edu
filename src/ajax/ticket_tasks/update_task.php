<?php
require("helpdbconnect.php");
require("block_file.php");
require("functions.php");
require("ticket_utils.php");

$post_filtered = filter_input_array(INPUT_POST, [
    "task_id" => FILTER_VALIDATE_INT,
	"new_status" => FILTER_VALIDATE_INT,
	"update_type" => FILTER_SANITIZE_STRING
]);

$task_id = $post_filtered["task_id"];
$new_status = $post_filtered["new_status"];
$update_type = $post_filtered["update_type"];
$username = $_SESSION["username"];

if (!user_is_tech($username)) {
	log_app(LOG_INFO, "[update_task.php] User is not a tech. Ignoring ajax request...");
	exit;
}

log_app(LOG_INFO, "[update_task.php] Setting status=$new_status for id=$task_id");

if ($update_type == "completed_change") {
	$query = "UPDATE help.ticket_tasks SET completed = ? WHERE id = ?";
} else if ($update_type == "required_change") {
	$query = "UPDATE help.ticket_tasks SET required = ? WHERE id = ?";
} else {
	log_app(LOG_INFO, "[update_task.php] Update type is not supported. Ignoring ajax request...");
	exit;
}

$update_status_res = $database->execute_query($query, [$new_status, $task_id]);

if (!$update_status_res) {
    log_app(LOG_ERR, "[update_task.php] Failed to execute query");
    http_response_code(500);
} else {
    log_app(LOG_INFO, "[update_task.php] Successfully updated task with update_type=$update_type for id=$task_id");
    http_response_code(200);
}