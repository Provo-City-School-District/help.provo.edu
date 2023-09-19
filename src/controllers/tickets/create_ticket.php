<?php
include("../../includes/header.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['can_create_tickets'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to view tickets.';
        exit;
    }
}
// require_once('../../includes/helpdbconnect.php');

?>
<article id="ticketWrapper">
    
    <h1>Create Ticket</h1>
    <!-- Form for updating ticket information -->
    <form method="POST" action="insert_ticket.php">
        <input type="hidden" name="client" value="<?= $_SESSION['username'] ?>">
        <label for="location">Location:</label>
        <input type="text" id="location" name="location" value=""><br>

        <label for="room">Room:</label>
        <input type="text" id="room" name="room" value=""><br>

        <label for="name">Ticket Title:</label>
        <input type="text" id="name" name="name" value=""><br>

        <label for="description">Ticket Description:</label>
        <textarea id="description" name="description"></textarea><br>
        
        <!-- Add a submit button to update the information -->
        <input type="submit" value="Create Ticket">
    </form>
</article>

<?php include("../../includes/footer.php"); ?>