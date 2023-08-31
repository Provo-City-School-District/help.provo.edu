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

?>

<h1> Tickets Page</h1>

<?php include("includes/footer.php"); ?>