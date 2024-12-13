<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

$username = $_SESSION["username"];
if (!user_is_tech($username)) {
	log_app(LOG_INFO, "[delete_task.php] User is not a tech. Ignoring task request...");
	http_response_code(401);
	exit;
}

$ticket_id = $_POST['ticket_id'];
$ticket_desc = strip_tags($_POST['task_description']);
$assigned_tech = $_POST['assigned_tech'];
if (empty($assigned_tech)) {
    $assigned_tech = null;
}
$form_task_complete = $_POST['task_complete'];
$form_task_required = $_POST['required'];

$task_complete = 0;
if (isset($form_task_complete)) {
    $task_complete = 1;
}

$task_required = 0;
if (isset($form_task_required)) {
    $task_required = 1;
}

$res = HelpDB::get()->execute_query("INSERT INTO help.ticket_tasks (ticket_id, description, required, completed, assigned_tech) VALUES (?, ?, ?, ?, ?)", [$ticket_id, $ticket_desc, $task_required, $task_complete, $assigned_tech]);
logTicketChange(HelpDB::get(), $ticket_id, $username, "Task \"$ticket_desc\" created", "", "");
header("Location: edit_ticket.php?id=$ticket_id");
exit();