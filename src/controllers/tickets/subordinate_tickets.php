<?php
include("header.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['is_supervisor'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to view tickets.';
        exit;
    }
}
require_once('helpdbconnect.php');
require_once(from_root("/includes/block_file.php"));
require_once(from_root("/includes/ticket_utils.php"));
require_once(from_root("/includes/tickets_template.php"));
require_once(from_root("/includes/alerts_template.php"));

$username = $_SESSION['username'];

if ($_SESSION['permissions']['is_supervisor'] == 1) {
    //Tickets query
    $ticket_query = <<<STR
    SELECT tickets.* 
    FROM tickets 
    JOIN users ON tickets.employee = users.username 
    WHERE users.supervisor_username = '$username' 
    AND tickets.status NOT IN ('closed', 'resolved') 
    ORDER BY tickets.last_updated DESC
    STR;

    $ticket_result = mysqli_query($database, $ticket_query);

    //Alerts query
    $alerts_query = <<<STR
    SELECT alerts.* 
    FROM alerts 
    JOIN users ON alerts.employee = users.username
    WHERE users.supervisor_username = '$username' 
    STR;

    $alerts_result = mysqli_query($database, $alerts_query);

    echo ' <h1>Subordinate Tickets</h1>';
    display_ticket_alerts($alerts_result);
    display_tickets_table($ticket_result, $database);
}
include("footer.php");

?>