<?php
require("helpdbconnect.php");
require("block_file.php");
require("functions.php");
require("ticket_utils.php");

$post_filtered = filter_input_array(INPUT_POST, [
    "ticket_id" => FILTER_VALIDATE_INT,
]);

$ticket_id = $post_filtered["ticket_id"];
$userID = get_id_for_user($_SESSION["username"]);

if (!isset($ticket_id)) {
    log_app(LOG_ERR, "[unread_ticket.php] Failed to get ticket_id");
    http_response_code(400);
    exit;
}


$delete_res = HelpDB::get()->execute_query("DELETE FROM ticket_viewed WHERE user_id = ? AND ticket_id = ?", [$userID, $ticket_id]);

if (!$delete_res) {
    log_app(LOG_ERR, "[unread_ticket.php] Failed to delete from ticket_viewed");
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to delete entry"]);
} else {
    log_app(LOG_INFO, "[unread_ticket.php] Successfully deleted entry from ticket_viewed for ticket $ticket_id");
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Entry deleted successfully"]);
}
