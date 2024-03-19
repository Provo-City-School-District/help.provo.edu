<?php
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

// Todays date
$today = new DateTime();

try {
    $client_response_tickets_query = "SELECT * FROM tickets WHERE priority = 15";
    $client_response_tickets_results = $database->execute_query($client_response_tickets_query);
    $client_response_tickets = $client_response_tickets_results->fetch_all(MYSQLI_ASSOC);

    foreach ($client_response_tickets as $ticket) {
        // process each ticket
        echo $ticket['id'] . ", " . $ticket['name'] . "\n";
    }
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
