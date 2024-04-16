<?php
require("helpdbconnect.php");
require("block_file.php");
require("functions.php");
require("ticket_utils.php");

$post_filtered = filter_input_array(INPUT_POST, [
    "task_id" => FILTER_VALIDATE_INT,
	"new_status" => FILTER_VALIDATE_INT
]);

$task_id = $post_filtered["task_id"];
$new_status = $post_filtered["new_status"];
$username = $_SESSION["username"];

if (!user_is_tech($username)) {
	log_app(LOG_INFO, "[update_task_required.php] User is not a tech. Ignoring ajax request...");
	exit;
}

log_app(LOG_INFO, "[update_task_required.php] Setting status=$new_status for id=$task_id");

$update_status_res = $database->execute_query("UPDATE help.ticket_tasks SET required = ? WHERE id = ?", [$new_status, $task_id]);

if (!$update_status_res) {
    log_app(LOG_ERR, "[update_task_required.php] Failed to execute query");
    http_response_code(500);
} else {
    log_app(LOG_INFO, "[update_task_required.php] Successfully updated task status for id=$task_id");
    http_response_code(200);
}