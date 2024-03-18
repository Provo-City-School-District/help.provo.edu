<?php
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

// Todays date
$today = new DateTime();

try {
    $stmt = $database->prepare("SELECT * FROM tickets WHERE priority = 15");
    $stmt->execute();

    $result = $stmt->get_result();
    $tickets = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($tickets as $ticket) {
        // process each ticket
        echo $ticket['id'] . ", " . $ticket['name'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
