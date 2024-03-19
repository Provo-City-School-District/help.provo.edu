<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

$ticket_id = $_POST['ticket_id'];
$ticket_desc = $_POST['task_description'];
$form_task_complete = $_POST['task_complete'];

$task_complete = 0;
if (isset($form_task_complete)) {
    $task_complete = 1;
}

$res = $database->execute_query("INSERT INTO help.ticket_tasks (ticket_id, description, completed) VALUES (?, ?, ?)", [$ticket_id, $ticket_desc, $task_complete]);

header("Location: edit_ticket.php?id=$ticket_id");
exit();