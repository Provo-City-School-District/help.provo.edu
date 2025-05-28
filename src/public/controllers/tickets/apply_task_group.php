<?php
require_once('init.php');
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

header('Content-Type: application/json');

$username = $_SESSION["username"];
$data = json_decode(file_get_contents('php://input'), true);
$ticket_id = filter_var($data['ticket_id'], FILTER_SANITIZE_NUMBER_INT);
$task_group = filter_var($data['task_group'], FILTER_SANITIZE_STRING);

if (!$ticket_id || !$task_group) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

// Fetch tasks from the selected group
$tasks_query = "SELECT * FROM task_templates WHERE template_group = ?";
$tasks_result = HelpDB::get()->execute_query($tasks_query, [$task_group]);

if (!$tasks_result) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch tasks for the selected group.']);
    exit;
}

// Add tasks to the ticket
while ($task = $tasks_result->fetch_assoc()) {
    $insert_task_query = "INSERT INTO ticket_tasks (ticket_id, description, required, assigned_tech) VALUES (?, ?, ?, ?)";
    $task_description = htmlspecialchars(trim($task['description']), ENT_QUOTES, 'UTF-8');
    $insert_result = HelpDB::get()->execute_query($insert_task_query, [
        $ticket_id,
        $task_description,
        $task['required'],
        $task['assigned_tech']
    ]);

    logTicketChange(HelpDB::get(), $ticket_id, $username, "Task \"$task_description\" created", "", "");

    if (!$insert_result) {
        echo json_encode(['success' => false, 'message' => 'Failed to add tasks to the ticket.']);
        exit;
    }
}

echo json_encode(['success' => true]);
