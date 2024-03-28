<?php
require_once("block_file.php");
require_once('helpdbconnect.php');


// Construct the SQL query based on the selected search options
$ticket_query = "SELECT * FROM tickets WHERE 1=1 ";

// Check if form data is set
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo 'submitted for Client Tickets Properly';
    // Get the search terms from the form
    $search_id = isset($_GET['search_id']) ? mysqli_real_escape_string($database, $_GET['search_id']) : '';
    $search_archived = isset($_GET['search_archived']) ? mysqli_real_escape_string($database, $_GET['search_archived']) : '';

    if ($search_archived == 1) {
        include __DIR__ . '/search_archived_tickets_query_builder.php';
    }
    print_r($ticket_query);
} else {
    echo "Request Method incorrect!";
}
