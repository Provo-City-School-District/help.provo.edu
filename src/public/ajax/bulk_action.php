<?php
require "block_file.php";
require "ticket_utils.php";
require "helpdbconnect.php";


// Only admins are allowed to do bulk actions for now
if (!session_is_admin()) {
    log_app(LOG_INFO, "[bulk_action.php] Session user is not an admin. Ignoring request...");
    http_response_code(403);
    exit;
}

$ticket_ids = $_POST["ticket_ids"];
$ticket_action = $_POST["ticket_action"];

foreach ($ticket_ids as $ticket_id) {
    if ($ticket_action == "resolve") {
        set_field_for_ticket($ticket_id, "status", "resolved");
    } else if ($ticket_action == "close") {
        set_field_for_ticket($ticket_id, "status", "closed");
    } else if ($ticket_action == "assign") {
        $new_assigned_tech = $_POST["assigned_tech"];
        set_field_for_ticket($ticket_id, "employee", $new_assigned_tech);
    }
}

http_response_code(200);