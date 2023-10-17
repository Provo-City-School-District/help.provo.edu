<?php
include("includes/header.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['can_view_tickets'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to view tickets.';
        exit;
    }
}
require_once('includes/helpdbconnect.php');
include("controllers/tickets/ticket_utils.php");
?>

<?php
if ($_SESSION['permissions']['is_tech'] == 1) {
    include("controllers/tickets/assigned_tickets.php");
} else {
    include("controllers/tickets/client_tickets.php");
}

?>

<?php include("includes/footer.php"); ?>