<?php
require_once("block_file.php");
require_once(from_root("/includes/tickets_template.php"));

$username = $_SESSION['username'];
// Execute the SQL query
$ticket_query = <<<STR
    SELECT *
    FROM tickets
    WHERE status NOT IN ('Closed', 'Resolved')
    AND employee = '$username'
    ORDER BY id ASC
    STR;

$ticket_result = mysqli_query($database, $ticket_query);
$tickets = mysqli_fetch_all($ticket_result, MYSQLI_ASSOC);
?>

<h1>My Assigned Tickets</h1>

<?php display_tickets_table($tickets, $database); ?>