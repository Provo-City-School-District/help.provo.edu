<?php
require_once("block_file.php");
include("header.php");
require_once('helpdbconnect.php');
if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['can_create_tickets'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to Create tickets.';
        exit;
    }
}
include("status_popup.php");

// Check if an error message is set
if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}

$usernamesQuery = "SELECT username,is_tech FROM users WHERE is_tech = 1 ORDER BY username ASC";
$usernamesResult = mysqli_query($database, $usernamesQuery);

if (!$usernamesResult) {
    die('Error fetching usernames: ' . mysqli_error($database));
}

// Store the usernames in an array
$techusernames = [];
while ($usernameRow = mysqli_fetch_assoc($usernamesResult)) {
    if ($usernameRow['is_tech'] == 1) {
        $techusernames[] = $usernameRow['username'];
    }
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
                    <option value=""></option>
                    <?php
                    // Query the sites table to get the site information
                    $location_query = "SELECT sitenumber, location_name FROM locations ORDER BY location_name ASC";
                    $location_result = mysqli_query($database, $location_query);
                    // Loop through the results and create an option for each site
                    while ($locations = mysqli_fetch_assoc($location_result)) {
                        $selected = '';
                        if ($locations['sitenumber'] == $locations['location_name']) {
                            $selected = 'selected';
                        }
                        echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
                    }
                    ?>
                </select required>
            </div>
            <div>
                <label for="room">Room:</label>
                <input type="text" id="room" name="room" value="<?= isset($_GET['room']) ? htmlspecialchars($_GET['room']) : '' ?>">
            </div>
            <div>
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone">
            </div>
            <div>
                <label for="cc_emails">Cc</label>
                <input type="text" id="cc_emails" name="cc_emails" value="<?= isset($_GET['cc_emails']) ? htmlspecialchars($_GET['cc_emails']) : '' ?>">
            </div>
            <div>
                <label for="bcc_emails">Bcc</label>
                <input type="text" id="bcc_emails" name="bcc_emails" value="<?= isset($_GET['bcc_emails']) ? htmlspecialchars($_GET['bcc_emails']) : '' ?>">
            </div>

            <!-- Conditionally show assigned tech view -->
            <?php
            if ($_SESSION["permissions"]["is_tech"]) :
            ?>
                <div>
                    <label for="assigned">Assigned to</label>
                    <select id="assigned" name="assigned">
                        <option value="unassigned">Unassigned</option>
                        <?php
                        foreach ($techusernames as $techusername) {
                            echo "<option value=\"$techusername\">$techusername</option>";
                        }
                        ?>
                    </select>
                </div>
            <?php endif ?>

        </div>
        <div class="detailContainer">
            <div class="grid2 ticketSubject">
                <label for="name">Ticket Title:</label>
                <input type="text" id="name" name="name" value="<?= isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '' ?>" maxlength="100">
            </div>

            <label for="description" class="heading2">Ticket Description:</label>
            <textarea id="description" name="description" class="tinyMCEtextarea"><?= isset($_GET['description']) ? htmlspecialchars($_GET['description']) : '' ?></textarea>
        </div>

        <div id="attachment-fields">
            <label for="attachment">Attachment:</label>
            <input type="file" id="attachment" name="attachment[]" type="file" multiple>
        </div>
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
<?php include("footer.php"); ?>