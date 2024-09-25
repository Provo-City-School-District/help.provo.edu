<?php
require "helpdbconnect.php";

$task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
$updated_assigned_tech = $_POST['assigned_tech'];
$updated_description = strip_tags($_POST['description']);

if (array_key_exists('required', $_POST) && $_POST['required'] == "required") {
    $updated_required = 1;
} else {
    $updated_required = 0;
}

$update_task_query = <<<STR
    UPDATE 
        ticket_tasks 
    SET
        assigned_tech = ?,
        required = ?,
        description = ?
    WHERE
        id = ?
STR;

$update_result = HelpDB::get()->execute_query($update_task_query, [
    $updated_assigned_tech, $updated_required, 
    $updated_description, $task_id
]);

if (!$update_result) {
    echo "Failed to update task";
    die;
}

$ticket_result = HelpDB::get()->execute_query("SELECT ticket_id FROM ticket_tasks WHERE id = ?", [$task_id]);
$ticket_id = $ticket_result->fetch_assoc()["ticket_id"];

header("Location: /controllers/tickets/edit_ticket.php?id=$ticket_id");