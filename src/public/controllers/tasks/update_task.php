<?php
require "helpdbconnect.php";
require "ticket_utils.php";

$task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
$updated_assigned_tech = $_POST['assigned_tech'];
$updated_description = strip_tags($_POST['description']);

if (array_key_exists('required', $_POST) && $_POST['required'] == "required") {
    $updated_required = 1;
} else {
    $updated_required = 0;
}

$old_task_data_query = <<<STR
    SELECT
        assigned_tech, ticket_id
    FROM
        ticket_tasks
    WHERE
        id = ?
STR;
$old_task_result = HelpDB::get()->execute_query($old_task_data_query, [$task_id]);
$old_task_data = $old_task_result->fetch_assoc();

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

$ticket_id = $old_task_data["ticket_id"];
// Send email to new employee for task assignment
if (isset($updated_assigned_tech) && $updated_assigned_tech != $old_task_data["assigned_tech"]) {
    $tech_name = get_client_name($updated_assigned_tech);
    $firstname = $tech_name["firstname"];
    $lastname = $tech_name["lastname"];

    $task_subject = "A task on ticket $ticket_id has been reassigned to $firstname $lastname";
    $template = new Template(from_root("/includes/templates/task_assigned.phtml"));

    $template->assigned_tech_name = $firstname." ".$lastname;
    $template->ticket_id = $ticket_id;
    $template->site_url = getenv('ROOTDOMAIN');
    $template->description = $updated_description;

    $assigned_tech_email = email_address_from_username($updated_assigned_tech);

    $res = send_email_and_add_to_ticket($ticket_id, $assigned_tech_email, $task_subject, $template);
    if (!$res) {
        $_SESSION['current_status'] = "Failed to send assigned task email to $assigned_tech_email";
        $_SESSION['status_type'] = 'error';
    }
}

header("Location: /controllers/tickets/edit_ticket.php?id=$ticket_id");