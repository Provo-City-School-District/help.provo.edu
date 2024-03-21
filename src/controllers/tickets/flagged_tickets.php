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
//page query
$username = $_SESSION['username'];
$ticket_query = <<<STR
        SELECT tickets.*, GROUP_CONCAT(DISTINCT alerts.alert_level) AS alert_levels
        FROM tickets
        LEFT JOIN alerts ON tickets.id = alerts.ticket_id
        WHERE tickets.id IN (
            SELECT flagged_tickets.ticket_id 
            FROM flagged_tickets 
            WHERE flagged_tickets.user_id IN (
                SELECT users.id 
                FROM users 
                WHERE users.username = ?
            )
        )
        GROUP BY tickets.id
        STR;
$ticket_result = $database->execute_query($ticket_query, [$username]);

?>

<h1>Flagged Tickets</h1>

<?php display_tickets_table($ticket_result, $database); ?>

<?php include("footer.php"); ?>