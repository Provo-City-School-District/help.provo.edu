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

//Alerts query
$alerts_query = <<<alerts_query
SELECT alerts.* 
FROM alerts 
JOIN users ON alerts.employee = users.username
JOIN tickets ON alerts.ticket_id = tickets.id
WHERE tickets.location = $managed_location
alerts_query;

$alerts_result = mysqli_query($database, $alerts_query);


SELECT * 
FROM tickets 
WHERE location = '" . $managed_location . "'
AND tickets.status NOT IN ('closed', 'resolved')";

$ticket_result = mysqli_query($database, $location_tickets_query);

//query for unassigned tickets for location
$unassigned_ticket_query = <<<unassigned_tickets
SELECT *
FROM tickets
WHERE status NOT IN ('closed', 'resolved') 
AND (employee IS NULL OR employee = 'unassigned')
AND location = $managed_location
unassigned_tickets;

$unassigned_ticket_result = mysqli_query($database, $unassigned_ticket_query);

<?php
//display alerts
display_ticket_alerts($alerts_result); ?>
<h2>Unassigned Tickets For Location <?= $managed_location ?> </h2>
<?php
//display unassigned tickets
display_tickets_table($unassigned_ticket_result, $database);
?>

<?php include("footer.php"); ?>