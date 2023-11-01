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
include("../../includes/status_popup.php");

// Check if an error message is set
if (isset($_SESSION['current_status'])) {
    $status_title = "";

    $status_type = $_SESSION['status_type'];
    if ($status_type == "success") {
        $status_title = "Success";
    } else if ($status_type == "error") {
        $status_title = "Error";
    } else if ($status_type == "info") {
        $status_title = "Info";
    } else {
        die("status_type is not recognized");
    }

    $status_popup = new Template("../../includes/status_popup.phtml");
    $status_popup->message_body = $_SESSION['current_status'];
    $status_popup->message_title = $status_title;
    $status_popup->alert_type = $status_type;

    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}
?>

<article id="ticketWrapper">
    <h1>Create Ticket</h1>
    <!-- Form for updating ticket information -->
    <form method="POST" action="insert_ticket.php" enctype="multipart/form-data">
        <input type="hidden" name="client" value="<?= $_SESSION['username'] ?>">
        <div class="ticketGrid">
            <div>
                <label for="location">Location:</label>
                <select id="location" name="location">
                    <?php
                    // Query the sites table to get the site information
                    $location_query = "SELECT sitenumber, location_name FROM locations ORDER BY location_name ASC";
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
            </div>
            <div>
                <label for="room">Room:</label>
                <input type="text" id="room" name="room" value="<?= isset($_GET['room']) ? htmlspecialchars($_GET['room']) : '' ?>">
            </div>
            <div>
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            <div>
                <label for="cc_emails">Cc</label>
                <input type="text" id="cc_emails" name="cc_emails" value="<?= isset($_GET['cc_emails']) ? htmlspecialchars($_GET['cc_emails']) : '' ?>">
            </div>
            <div>
                <label for="bcc_emails">Bcc</label>
                <input type="text" id="bcc_emails" name="bcc_emails" value="<?= isset($_GET['bcc_emails']) ? htmlspecialchars($_GET['bcc_emails']) : '' ?>">
            </div>
        </div>
        <div class="detailContainer">
            <div class="grid2 ticketSubject">
                <label for="name">Ticket Title:</label>
                <input type="text" id="name" name="name" value="<?= isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '' ?>">
            </div>

            <label for="description" class="heading2">Ticket Description:</label>
            <textarea id="description" name="description" class="tinyMCEtextarea"><?= isset($_GET['description']) ? htmlspecialchars($_GET['description']) : '' ?></textarea>
        </div>

        <div id="attachment-fields">
            <label for="attachment">Attachment:</label>
            <input type="file" id="attachment" name="attachment[]" type="file" multiple>
        </div>
        <!-- Commented out since the input we have allows multi file uploads -->
        <!-- <button type="button" id="add-attachment-field">Add Attachment</button> -->

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