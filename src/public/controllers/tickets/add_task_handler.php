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

$_SESSION['current_status'] = "Task created successfully";
$_SESSION['status_type'] = 'success';

if (isset($assigned_tech)) {
    // Send email to new employee for task assignment
    $tech_name = get_client_name($assigned_tech);
    $firstname = $tech_name["firstname"];
    $lastname = $tech_name["lastname"];

    $task_subject = "A task on ticket $ticket_id has been created and assigned to $firstname $lastname";
    $template = new Template(from_root("/includes/templates/task_created.phtml"));

    $template->assigned_tech_name = $firstname." ".$lastname;
    $template->ticket_id = $ticket_id;
    $template->site_url = getenv('ROOTDOMAIN');
    $template->description = $ticket_desc;

    $assigned_tech_email = email_address_from_username($assigned_tech);

    $res = send_email_and_add_to_ticket($ticket_id, $assigned_tech_email, $task_subject, $template);
    if (!$res) {
        $_SESSION['current_status'] = "Failed to send assigned task email to $assigned_tech_email";
        $_SESSION['status_type'] = 'error';
    }
}

$res = HelpDB::get()->execute_query("INSERT INTO help.ticket_tasks (ticket_id, description, required, completed, assigned_tech) VALUES (?, ?, ?, ?, ?)", [$ticket_id, $ticket_desc, $task_required, $task_complete, $assigned_tech]);
logTicketChange(HelpDB::get(), $ticket_id, $username, "Task \"$ticket_desc\" created", "", "");
header("Location: edit_ticket.php?id=$ticket_id");
exit();