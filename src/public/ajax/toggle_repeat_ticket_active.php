<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');

$username = $_SESSION["username"];
if (!user_is_tech($username)) {
    log_app(LOG_INFO, "[toggle_repeat_ticket_active.php] User is not a tech. Ignoring request...");
    http_response_code(401);
    exit;
}

header('Content-Type: application/json');

$ticket_id = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);
$active = filter_input(INPUT_POST, 'active', FILTER_VALIDATE_INT);

log_app(LOG_INFO, "[toggle_repeat_ticket_active.php] Toggling repeatable ticket active status for ticket ID: $ticket_id, active: $active");

if ($ticket_id === null || $ticket_id === false || $active === null || $active === false) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$stmt = HelpDB::get()->prepare("UPDATE repeatable_ticket_templates SET active = ? WHERE id = ?");
$stmt->bind_param('ii', $active, $ticket_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'DB error']);
}
