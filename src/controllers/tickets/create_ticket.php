<?php
include("../../includes/header.php");
require_once('../../includes/helpdbconnect.php');
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
    <form method="POST" action="insert_ticket.php" enctype="multipart/form-data">
        <input type="hidden" name="client" value="<?= $_SESSION['username'] ?>">
        <label for="location">Location:</label>
        <select id="location" name="location">
            <?php
            // Query the sites table to get the site information
            $location_query = "SELECT sitenumber, location_name FROM locations";
            $location_result = mysqli_query($database, $location_query);
            // Loop through the results and create an option for each site
            while ($locations = mysqli_fetch_assoc($location_result)) {
                $selected = '';
                if ($locations['sitenumber'] == $row['location']) {
                    $selected = 'selected';
                }
                echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
            }
            ?>
        </select>
        <br>
        <label for="room">Room:</label>
        <input type="text" id="room" name="room" value="<?= isset($_GET['room']) ? htmlspecialchars($_GET['room']) : '' ?>"><br>

        <label for="phone">Phone:</label>
        <input type="tel" id="phone" name="phone" required><br>
        <label for="name">Ticket Title:</label>
        <input type="text" id="name" name="name" value="<?= isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '' ?>"><br>

        <label for="description">Ticket Description:</label>
        <textarea id="description" name="description" class="tinyMCEtextarea"><?= isset($_GET['description']) ? htmlspecialchars($_GET['description']) : '' ?></textarea><br>

        <div id="attachment-fields">
            <label for="attachment">Attachment:</label>
            <input type="file" id="attachment" name="attachment[]"><br>
        </div>

        <button type="button" id="add-attachment-field">Add Attachment</button><br>
        <!-- Add a submit button to create the ticket -->
        <input type="submit" value="Create Ticket">
    </form>
</article>
<script>
    // Add a new file input field when the "Add Attachment" button is clicked
    document.getElementById('add-attachment-field').addEventListener('click', function() {
        var attachmentFields = document.getElementById('attachment-fields');
        var newAttachmentField = document.createElement('div');
        newAttachmentField.innerHTML = '<label for="attachment">Attachment:</label><input type="file" id="attachment" name="attachment[]"><br>';
        attachmentFields.appendChild(newAttachmentField);
    });
</script>
<?php include("../../includes/footer.php"); ?>