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

$ticket_client_res = $database->execute_query("SELECT client FROM tickets WHERE tickets.id = ?", [$ticket_id]);
$ticket_client_data = mysqli_fetch_assoc($ticket_client_res);

$ticket_client = $ticket_client_data["client"];
if ($username != $ticket_client) {
    log_app(LOG_ERR, "[close_ticket.php] Client ($ticket_client) does not match the active user ($username)");
    http_response_code(401);
    exit;
}

$res = $database->execute_query("UPDATE tickets SET status = 'closed' WHERE tickets.id = ?", [$ticket_id]);
if (!$res) {
    log_app(LOG_ERR, "[close_ticket.php] Failed to execute query");
    http_response_code(500);
} else {
    log_app(LOG_INFO, "[close_ticket.php] Successfully closed ticket $ticket_id");
    http_response_code(200);
}