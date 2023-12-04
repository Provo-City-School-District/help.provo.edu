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

$username = $_SESSION['username'];

if ($_SESSION['permissions']['is_tech'] == 1) {
    // User is a tech
    echo '<h1>My Assigned Tickets</h1>';

    // SQL query for tickets 
    $ticket_query = <<<STR
    SELECT *
    FROM tickets
    WHERE status NOT IN ('Closed', 'Resolved')
    AND employee = '$username'
    ORDER BY id ASC
    STR;

    $ticket_result = mysqli_query($database, $ticket_query);
    $tech_tickets = mysqli_fetch_all($ticket_result, MYSQLI_ASSOC);
    display_tickets_table($tech_tickets, $database);
} else {
    // User is a client
    echo '<h1>My Tickets</h1>';

    // SQL query for Tickets
    $ticket_query = <<<STR
    SELECT *
    FROM tickets
    WHERE status NOT IN ('Closed', 'Resolved')
    AND client = '$username'
    ORDER BY id ASC
    STR;

    $ticket_result = mysqli_query($database, $ticket_query);
    $client_tickets = mysqli_fetch_all($ticket_result, MYSQLI_ASSOC);
    display_tickets_table($client_tickets, $database);
}

include("footer.php");
