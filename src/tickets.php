<?php
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
require_once(from_root("/includes/ticket_utils.php"));
require_once(from_root("/includes/block_file.php"));
require_once(from_root("/includes/tickets_template.php"));
require_once(from_root("/includes/alerts_template.php"));

$username = $_SESSION['username'];

// SQL query for tech tickets 
$tech_ticket_query = <<<STR
    SELECT *
    FROM tickets
    WHERE status NOT IN ('Closed', 'Resolved')
    AND employee = ?
    ORDER BY id ASC
    STR;
$tech_ticket_result = $database->execute_query($tech_ticket_query, [$username]);
$tech_tickets = mysqli_fetch_all($tech_ticket_result, MYSQLI_ASSOC);

// SQL query for client Tickets
$client_ticket_query = <<<STR
    SELECT *
    FROM tickets
    WHERE status NOT IN ('Closed', 'Resolved')
    AND client = ?
    AND employee != ?
    ORDER BY id ASC
    STR;
$client_ticket_result = $database->execute_query($client_ticket_query, [$username, $username]);
$client_tickets = mysqli_fetch_all($client_ticket_result, MYSQLI_ASSOC);

// Query the alerts for tech
$alert_result = $database->execute_query("SELECT * FROM alerts WHERE employee = ? AND supervisor_alert = 0", [$username]);
$alerts = mysqli_fetch_all($alert_result, MYSQLI_ASSOC);


if ($_SESSION['permissions']['is_tech'] == 1) {
    // Display the alerts
    display_ticket_alerts($alerts);

    //display tickets assigned to user
    echo '<h1>My Assigned Tickets</h1>';
    display_tickets_table($tech_tickets, $database);
}
// Display Tickets that have user as client
echo '<h1>My Open Tickets</h1>';

display_tickets_table($client_tickets, $database);


include("footer.php");
