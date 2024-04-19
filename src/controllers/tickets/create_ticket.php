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

//variables
$techusername = $_SESSION['username'];

// Check if an error message is set
if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}

$usernamesResult = $database->execute_query("SELECT username,is_tech FROM users WHERE is_tech = 1 ORDER BY username ASC");

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
                <label for="location">Department/Location:</label>
                <select id="location" name="location">
                    <option value=""></option>
                    <?php
                    // Query the locations table to get the departments
                    $department_result = $database->execute_query("SELECT * FROM locations WHERE is_department = TRUE ORDER BY location_name ASC");

                    // Create a "Department" optgroup and create an option for each department
                    echo '<optgroup label="Department">';
                    while ($locations = mysqli_fetch_assoc($department_result)) {
                        $selected = '';
                        if (isset($_GET['location']) && $locations['sitenumber'] == $_GET['location']) {
                            $selected = 'selected';
                        } else {
                            $loc = get_fast_client_location($_SESSION["username"]);
                            if ($locations['sitenumber'] == $loc) {
                                $selected = 'selected';
                            }
                        }
                        echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
                    }
                    echo '</optgroup>';

                    // Query the locations table to get the locations
                    $location_result = $database->execute_query("SELECT * FROM locations WHERE is_department = FALSE ORDER BY location_name ASC");

                    // Create a "Location" optgroup and create an option for each location
                    echo '<optgroup label="Location">';
                    while ($locations = mysqli_fetch_assoc($location_result)) {
                        $selected = '';
                        if (isset($_GET['location']) && $locations['sitenumber'] == $_GET['location']) {
                            $selected = 'selected';
                        } else {
                            $loc = get_fast_client_location($_SESSION["username"]);
                            if ($locations['sitenumber'] == $loc) {
                                $selected = 'selected';
                            }
                        }
                        echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
                    }
                    echo '</optgroup>';
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
                        foreach ($techusernames as $username) {
                            $selected = ($username == $techusername) ? 'selected' : '';
                            echo "<option value=\"$username\" $selected>$username</option>";
                        }
                        ?>
                    </select>
                </div>
            <?php endif ?>

        </div>
        <div class="detailContainer">
            <div class="grid2 ticketSubject">
                <label for="ticket_name">Ticket Title:</label>
                <input type="text" id="ticket_name" name="ticket_name" value="<?= isset($_GET['ticket_name']) ? htmlspecialchars($_GET['ticket_name']) : '' ?>" maxlength="100">
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
    //================================= Prevent Form Double Submit =================================
    // Prevent Double Submits
    document.querySelectorAll("form").forEach((form) => {
        form.addEventListener("submit", (e) => {
            // Prevent if already submitting
            if (form.classList.contains("is-submitting")) {
                e.preventDefault();
            }

            // Add class to hook our visual indicator on
            form.classList.add("is-submitting");
        });
    });
</script>
<script src="/includes/js/jquery-3.7.1.min.js"></script>
<script src="/includes/js/pages/create_ticket.js?v=0.0.1"></script>
<?php include("footer.php"); ?>