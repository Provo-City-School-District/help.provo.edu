<?php
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

log_app(LOG_INFO, "active_ticket_clear.php running");

$res = $database->execute_query("UPDATE users SET active_ticket = NULL, active_ticket_updated = NULL WHERE active_ticket_updated < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
if (!$res) {
	log_app(LOG_ERR, "[active_ticket_clear.php] Failed to run query to clear active_ticket and active_ticket_updated");
} else {
	log_app(LOG_INFO, "[active_ticket_clear.php] Succesfully ran active_ticket clear query");
}