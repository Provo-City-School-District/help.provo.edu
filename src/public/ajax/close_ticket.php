<?php
require("helpdbconnect.php");
require("block_file.php");
require("functions.php");
require("ticket_utils.php");

$post_filtered = filter_input_array(INPUT_POST, [
    "ticket_id" => FILTER_VALIDATE_INT,
]);

$ticket_id = $post_filtered["ticket_id"];
$username = $_SESSION["username"];

if (!isset($ticket_id)) {
    log_app(LOG_ERR, "[close_ticket.php] Failed to get ticket_id");
    http_response_code(400);
    exit;
}

$ticket_client_res = HelpDB::get()->execute_query("SELECT client FROM tickets WHERE tickets.id = ?", [$ticket_id]);
$ticket_client_data = $ticket_client_res->fetch_assoc();

$ticket_client = $ticket_client_data["client"];
if ($username != $ticket_client) {
    log_app(LOG_ERR, "[close_ticket.php] Client ($ticket_client) does not match the active user ($username)");
    http_response_code(401);
    exit;
}

$old_ticket_res = HelpDB::get()->execute_query("SELECT * FROM tickets WHERE id = ?", [$ticket_id]);
$old_ticket_data = $old_ticket_res->fetch_assoc();

$updatedStatus = "closed";

$res = HelpDB::get()->execute_query("UPDATE tickets SET status = ? WHERE tickets.id = ?", [$updatedStatus, $ticket_id]);

if (isset($old_ticket_data['status']) && $old_ticket_data['status'] != $updatedStatus) {
    logTicketChange(HelpDB::get(), $ticket_id, $_SESSION['username'], "status", $old_ticket_data['status'], $updatedStatus);

    // Check if the ticket has an alert about not being updated in last 48 hours and clear it since the ticket was just updated.
    removeAlert(HelpDB::get(), $alert48Message, $ticket_id);
    removeAlert(HelpDB::get(), $alert7DayMessage, $ticket_id);
    removeAlert(HelpDB::get(), $alert15DayMessage, $ticket_id);
    removeAlert(HelpDB::get(), $alert20DayMessage, $ticket_id);
}

if (!$res) {
    log_app(LOG_ERR, "[close_ticket.php] Failed to execute query");
    http_response_code(500);
} else {
    log_app(LOG_INFO, "[close_ticket.php] Successfully closed ticket $ticket_id");
    http_response_code(200);
}