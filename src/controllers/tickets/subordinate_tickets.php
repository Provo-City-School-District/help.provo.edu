<?php
require_once("block_file.php");
require_once(from_root("/includes/tickets_template.php"));
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
include("ticket_utils.php");

//page query
$ticket_query = "
SELECT tickets.* 
FROM tickets 
JOIN users ON tickets.employee = users.username 
WHERE users.supervisor_username = '" . $_SESSION['username'] . "' 
AND tickets.status NOT IN ('closed', 'resolved') 
ORDER BY tickets.last_updated DESC";

$ticket_result = mysqli_query($database, $ticket_query);

?>

<h1>Subordinate Tickets</h1>

<?php display_tickets_table($ticket_result, $database); ?>

<?php include("footer.php"); ?>