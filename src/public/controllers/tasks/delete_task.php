<?php
require "block_file.php";
require "helpdbconnect.php";
require "functions.php";
require "ticket_utils.php";

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

$ticket_result = HelpDB::get()->execute_query("SELECT ticket_id FROM ticket_tasks WHERE id = ?", [$task_id]);
$ticket_id = $ticket_result->fetch_assoc()["ticket_id"];

log_app(LOG_INFO, "[delete_task.php] Deleting task id=$task_id");

$update_status_res = HelpDB::get()->execute_query("DELETE FROM help.ticket_tasks WHERE id = ?", [$task_id]);

if (!$update_status_res) {
    log_app(LOG_ERR, "[delete_task.php] Failed to execute query");
    http_response_code(500);
} else {
    log_app(LOG_INFO, "[delete_task.php] Successfully deleted task id=$task_id");
    http_response_code(200);

    // Redirect to the edit ticket page
    header("Location: /controllers/tickets/edit_ticket.php?id=$ticket_id");
}