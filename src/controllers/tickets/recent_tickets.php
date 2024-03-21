<?php
require_once("block_file.php");
require_once(from_root("/includes/tickets_template.php"));
include("header.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['can_view_tickets'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to view tickets.';
        exit;
    }
}
require_once('helpdbconnect.php');
include("ticket_utils.php");

$username = $_SESSION['username'];

$ticket_query = <<<QUERY
SELECT tickets.*, GROUP_CONCAT(DISTINCT alerts.alert_level) AS alert_levels
FROM tickets
LEFT JOIN notes ON tickets.id = notes.linked_id 
LEFT JOIN ticket_logs ON tickets.id = ticket_logs.ticket_id
LEFT JOIN alerts ON tickets.id = alerts.ticket_id
WHERE ((notes.creator = ? AND notes.created >= DATE_SUB(NOW(), INTERVAL 2 DAY)) 
OR (ticket_logs.user_id = ? AND ticket_logs.created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)))
GROUP BY tickets.id
QUERY;


$ticket_result = $database->execute_query($ticket_query, [$username, $username]);

?>

<h1>Recent Tickets</h1>

<?php display_tickets_table($ticket_result, $database); ?>

<?php include("footer.php"); ?>