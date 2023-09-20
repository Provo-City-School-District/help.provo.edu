<?php
include("../../includes/header.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['can_create_tickets'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to Create tickets.';
        exit;
    }
}
// require_once('../../includes/helpdbconnect.php');

?>
<article id="ticketWrapper">
    <?php
    // Check if a success message is set
    if (isset($_SESSION['error_message'])) {
        echo '<div class="error_message-message">' . $_SESSION['error_message'] . '</div>';

        // Unset the success message to clear it
        unset($_SESSION['error_message']);
    }
    ?>
    <h1>Create Ticket</h1>
    <!-- Form for updating ticket information -->
    <form method="POST" action="insert_ticket.php">
        <input type="hidden" name="client" value="<?= $_SESSION['username'] ?>">
        <label for="location">Location:</label>
        <input type="text" id="location" name="location" value="<?= isset($_GET['location']) ? htmlspecialchars($_GET['location']) : '' ?>"><br>

        <label for="room">Room:</label>
        <input type="text" id="room" name="room" value="<?= isset($_GET['room']) ? htmlspecialchars($_GET['room']) : '' ?>"><br>

        <label for="name">Ticket Title:</label>
        <input type="text" id="name" name="name" value="<?= isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '' ?>"><br>

        <label for="description">Ticket Description:</label>
        <textarea id="description" name="description"><?= isset($_GET['description']) ? htmlspecialchars($_GET['description']) : '' ?></textarea><br>

        <!-- Add a submit button to create the ticket -->
        <input type="submit" value="Create Ticket">
    </form>
</article>

<?php include("../../includes/footer.php"); ?>