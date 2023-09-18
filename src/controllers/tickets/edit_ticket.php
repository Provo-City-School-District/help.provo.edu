<?php
include("../../includes/header.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['can_view_tickets'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to view tickets.';
        exit;
    }
}
require_once('../../includes/helpdbconnect.php');
// Get the ticket ID from $_GET
$ticket_id = $_GET['id'];

// Query the ticket by ID and all notes for that ID
$query = "SELECT tickets.*, JSON_ARRAYAGG(notes.note) AS notes
          FROM tickets
          LEFT JOIN notes ON tickets.id = notes.linked_id
          WHERE tickets.id = $ticket_id
          GROUP BY tickets.id";
$result = mysqli_query($database, $query);

// Check if the query was successful
if (!$result) {
    die('Error: ' . mysqli_error($database));
}

// Fetch the ticket and notes from the result set
$row = mysqli_fetch_assoc($result);

?>
<a href="../../tickets.php">View All Tickets</a>
<article id="ticketWrapper">

<?php

// Display the ticket and notes
echo "Ticket ID: " . $row['id'] . "<br>";
echo "Ticket Title: " . $row['name'] . "<br>";
echo "Ticket Description: " . $row['description'] . "<br>";

// Loop through the notes and display them
foreach (json_decode($row['notes']) as $note) {
    echo "Note: " . $note . "<br>";
}

?>
</article>








<?php include("../../includes/footer.php"); ?>