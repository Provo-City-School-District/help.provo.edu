<?php
require_once("block_file.php");
require_once(from_root("/includes/tickets_template.php"));
include("header.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['is_location_manager'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to view tickets.';
        exit;
    }
}
require_once('helpdbconnect.php');
include("ticket_utils.php");

$managed_location = $_SESSION['permissions']['location_manager_sitenumber'];

//page query
$location_tickets_query = "
SELECT * 
FROM tickets 
WHERE location = '" . $managed_location . "'
AND tickets.status NOT IN ('closed', 'resolved')";

$ticket_result = mysqli_query($database, $location_tickets_query);

?>

<h1>Location Tickets</h1>

<?php display_tickets_table($ticket_result, $database); ?>

<?php include("footer.php"); ?>