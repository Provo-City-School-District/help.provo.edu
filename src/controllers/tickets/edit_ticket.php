<?php
ob_start();
include("ticket_utils.php");
$ticket_id = sanitize_numeric_input($_GET['id']);
// Check if the user is logged in
include("../../includes/header.php");
if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['can_view_tickets'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to view tickets.';
        exit;
    }
}
include("../../vendor/autoload.php");
require_once('../../includes/helpdbconnect.php');
require_once("../../includes/status_popup.php");

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
// Query the ticket by ID and all notes for that ID
$query = "SELECT
tickets.id,
tickets.client,
tickets.employee,
tickets.location,
tickets.room,
tickets.name,
tickets.description,
tickets.created,
tickets.last_updated,
tickets.due_date,
tickets.status,
tickets.attachment_path,
tickets.phone,
tickets.cc_emails,
tickets.bcc_emails,
tickets.priority,
tickets.request_type_id,
tickets.merged_into_id,
tickets.parent_ticket,
JSON_ARRAYAGG(
    JSON_OBJECT(
        'note_id', notes.note_id,
        'note', notes.note,
        'created', notes.created,
        'creator', notes.creator,
        'time', notes.time,
        'visible_to_client', notes.visible_to_client,
        'date_override', notes.date_override
    )
    ORDER BY notes.created
) AS notes
FROM
tickets
LEFT JOIN
notes
ON
tickets.id = notes.linked_id
WHERE
tickets.id = $ticket_id
GROUP BY
tickets.id
";
$result = mysqli_query($database, $query);

// Check if the query was successful
if (!$result) {
    die('Error: ' . mysqli_error($database));
}

// Fetch the ticket and notes from the result set
$ticket = mysqli_fetch_assoc($result);
$ticket_merged_id = $ticket["merged_into_id"];
$should_redirect = isset($_GET["nr"]) ? $_GET["nr"] != 1 : true;

if ($ticket_merged_id != null && $should_redirect) {
    header("Location: edit_ticket.php?id=$ticket_merged_id");
    die();
}
ob_end_flush();

// Fetch the list of usernames from the users table
$usernamesQuery = "SELECT username FROM users";
$usernamesResult = mysqli_query($database, $usernamesQuery);

if (!$usernamesResult) {
    die('Error fetching usernames: ' . mysqli_error($database));
}

// Store the usernames in an array
$usernames = [];
while ($usernameRow = mysqli_fetch_assoc($usernamesResult)) {
    $usernames[] = $usernameRow['username'];
}

//fetch child tickets
$child_ticket_query = "SELECT * FROM tickets WHERE parent_ticket = ?";
$child_ticket_stmt = $database->prepare($child_ticket_query);
$child_ticket_stmt->bind_param("i", $ticket_id);
$child_ticket_stmt->execute();

$child_ticket_result = $child_ticket_stmt->get_result();
$child_tickets = $child_ticket_result->fetch_all(MYSQLI_ASSOC);

?>
<article id="ticketWrapper">
    <h1>Ticket #<?= $ticket['id'] ?></h1><br>
    Created: <?= $ticket['created'] ?><br>
    Last Updated: <?= $ticket['last_updated'] ?><br>
    Due Date: <?= $ticket['due_date'] ?><br><br>
    <!-- Form for updating ticket information -->
    <form method="POST" action="update_ticket.php">
        <!-- Add a submit button to update the information -->
        <input type="submit" value="Update Ticket"><br>
        Send Emails on Update:<input type="checkbox" name="send_emails" value="send_emails"><br><br>
        <div class="ticketGrid">
            <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
            <input type="hidden" name="madeby" value="<?= $_SESSION['username'] ?>">
            <div>
                <label for="client">Client:</label>
                <!-- <input type="text" id="client" name="client" value="<?= $ticket['client'] ?>"> -->
                <select id="client" name="client">
                    <?php foreach ($usernames as $username) : ?>
                        <option value="<?= $username ?>" <?= $ticket['client'] === $username ? 'selected' : '' ?>><?= $username ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div> <label for="employee">Assigned Tech:</label>
                <select id="employee" name="employee">
                    <?php foreach ($usernames as $username) : ?>
                        <option value="<?= $username ?>" <?= $ticket['employee'] === $username ? 'selected' : '' ?>><?= $username ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="location">Location:</label>
                <select id="location" name="location">
                    <?php
                    // Query the sites table to get the site information
                    $location_query = "SELECT sitenumber, location_name FROM locations";
                    $location_result = mysqli_query($database, $location_query);
                    // Loop through the results and create an option for each site
                    while ($locations = mysqli_fetch_assoc($location_result)) {
                        $selected = '';
                        if ($locations['sitenumber'] == $ticket['location']) {
                            $selected = 'selected';
                        }
                        echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="room">Room:</label>
                <input type="text" id="room" name="room" value="<?= $ticket['room'] ?>">
            </div>
            <div>
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" value="<?= $ticket['phone'] ?>">
            </div>
            <div>
                <label for="request_type">Request Type:</label>
                <select id="request_type" name="request_type">
                    <option value="0">Select a more specific request type otherwise (Other)</option>
                    <?php
                    // Fetch the top-level request types
                    $topLevelQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_parent IS NULL ORDER BY request_name";
                    $topLevelResult = $database->query($topLevelQuery);

                    // Add the top-level request types as options
                    while ($topLevelRow = $topLevelResult->fetch_assoc()) {
                        $selected = '';
                        if ($topLevelRow['request_id'] == $ticket['request_type_id']) {
                            $selected = 'selected';
                        } else {
                            // Check if the ticket's request type is a child or grandchild of this top-level request type
                            $childQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_id = " . $ticket['request_type_id'] . " AND request_parent = " . $topLevelRow['request_id'];
                            $childResult = $database->query($childQuery);
                            if ($childResult->num_rows > 0) {
                                $selected = 'selected';
                            } else {
                                $grandchildQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_id = " . $ticket['request_type_id'];
                                $grandchildResult = $database->query($grandchildQuery);
                                while ($grandchildRow = $grandchildResult->fetch_assoc()) {
                                    $childQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_id = " . $grandchildRow['request_parent'] . " AND request_parent = " . $topLevelRow['request_id'];
                                    $childResult = $database->query($childQuery);
                                    if ($childResult->num_rows > 0) {
                                        $selected = 'selected';
                                    }
                                }
                            }
                        }
                        echo '<option disabled value="' . $topLevelRow['request_id'] . '" ' . $selected . '>' . $topLevelRow['request_name'] . '</option>';

                        // Fetch the child request types
                        $childQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_parent = " . $topLevelRow['request_id'] . " ORDER BY request_name";
                        $childResult = $database->query($childQuery);

                        // Add the child request types as options
                        while ($childRow = $childResult->fetch_assoc()) {
                            $selected = '';
                            if ($childRow['request_id'] == $ticket['request_type_id']) {
                                $selected = 'selected';
                            } else {
                                // Check if the ticket's request type is a grandchild of this child request type
                                $grandchildQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_id = " . $ticket['request_type_id'] . " AND request_parent = " . $childRow['request_id'];
                                $grandchildResult = $database->query($grandchildQuery);
                                if ($grandchildResult->num_rows > 0) {
                                    $selected = 'selected';
                                }
                            }
                            echo '<option value="' . $childRow['request_id'] . '" ' . $selected . '>&nbsp;&nbsp;&nbsp;&nbsp;' . $childRow['request_name'] . '</option>';

                            // Fetch the grandchild request types
                            $grandchildQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_parent = " . $childRow['request_id'] . " ORDER BY request_name";
                            $grandchildResult = $database->query($grandchildQuery);

                            // Add the grandchild request types as options
                            while ($grandchildRow = $grandchildResult->fetch_assoc()) {
                                $selected = ($grandchildRow['request_id'] == $ticket['request_type_id']) ? 'selected' : '';
                                echo '<option value="' . $grandchildRow['request_id'] . '" ' . $selected . '>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $grandchildRow['request_name'] . '</option>';
                            }
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- 
                   TODO: need to build out a way to over ride the due date. currently this field just and sets the value to the calculated due date.
                -->
            <div>
                <label for="due_date">Due Date:</label>
                <input type="date" id="due_date" name="due_date" value="<?= $ticket['due_date'] ?>">
            </div>
            <div>
                <label for="status">Current Status:</label>
                <select id="status" name="status">
                    <option value="open" <?= ($ticket['status'] == 'open') ? ' selected' : '' ?>>Open</option>
                    <option value="closed" <?= ($ticket['status'] == 'closed') ? ' selected' : '' ?>>Closed</option>
                    <option value="resolved" <?= ($ticket['status'] == 'resolved') ? ' selected' : '' ?>>Resolved</option>
                    <option value="pending" <?= ($ticket['status'] == 'pending') ? ' selected' : '' ?>>Pending</option>
                    <option value="vendor" <?= ($ticket['status'] == 'vendor') ? ' selected' : '' ?>>Vendor</option>
                    <option value="maintenance" <?= ($ticket['status'] == 'maintenance') ? ' selected' : '' ?>>Maintenance</option>
                </select>
            </div>
            <div>
                <label for="priority">Priority:</label>
                <select id="priority" name="priority">
                    <option value="1" <?= ($ticket['priority'] == '1') ? ' selected' : '' ?>>Critical</option>
                    <option value="3" <?= ($ticket['priority'] == '3') ? ' selected' : '' ?>>Urgent</option>
                    <option value="5" <?= ($ticket['priority'] == '5') ? ' selected' : '' ?>>High</option>
                    <option value="10" <?= ($ticket['priority'] == '10') ? ' selected' : '' ?>>Standard</option>
                    <option value="15" <?= ($ticket['priority'] == '15') ? ' selected' : '' ?>>Client Response</option>
                    <option value="30" <?= ($ticket['priority'] == '30') ? ' selected' : '' ?>>Project</option>
                    <option value="60" <?= ($ticket['priority'] == '60') ? ' selected' : '' ?>>Meeting Support</option>
                </select>
            </div>
            <div>
                <label for="parent_ticket">Parent Ticket:</label>
                <input type="number" id="parent_ticket" name="parent_ticket" value="<?= $ticket['parent_ticket'] ?>">
            </div>
            <div>
                <label for="cc_emails">CC:</label>
                <input type="text" id="cc_emails" name="cc_emails" value="<?= $ticket['cc_emails'] ?>">
            </div>
            <div>
                <label for="bcc_emails">BCC:</label>
                <input type="text" id="bcc_emails" name="bcc_emails" value="<?= $ticket['bcc_emails'] ?>">
            </div>
        </div>

        <div class="detailContainer">
            <div class="grid2 ticketSubject">
                <label for="name">Ticket Title:</label>
                <input type="text" id="name" name="name" value="<?= $ticket['name'] ?>">
            </div>
            <label for="description" class="heading2">Request Detail:</label>
            <div class="ticket-description">
                <?= html_entity_decode($ticket['description']) ?><br>
                <button id="edit-description-button" type="button">Edit Request Detail</button>
            </div>

            <div id="edit-description-form" style="display: none;">
                <textarea id="description" name="description" class="tinyMCEtextarea"><?= $ticket['description'] ?></textarea>
            </div>
        </div>

    </form>
    <!-- Loop through the notes and display them -->
    <?php
    if (isset($ticket['attachment_path']) && strlen($ticket['attachment_path']) > 8) {
        $attachmentPaths = explode(',', $ticket['attachment_path']);
    }

    // Output links to the file attachments
    if (!empty($attachmentPaths) && array_key_exists(0, $attachmentPaths)) {
    ?>
        <h2>Attachments:</h2>
        <ul>
            <?php
            foreach ($attachmentPaths as $attachmentPath) {
                echo '<li><a href="' . $attachmentPath . '">' . basename($attachmentPath) . '</a></li>';
            }
            ?>
        </ul>
    <?php
    }
    ?>
    <button id="toggle-file-upload-form">Attach Files</button>
    <div id="file-upload-form" style="display: none;">
        <h3>Upload Files</h3>
        <form method="post" action="upload_files_handler.php" enctype="multipart/form-data">
            <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
            <input type="hidden" name="username" value="<?= $_SESSION['username'] ?>">
            <label for="attachment">Attachment:</label>
            <input id="attachment" name="attachment[]" type="file" multiple>
            <input type="submit" value="Upload">
        </form>
    </div>
    <div id="maximum-file-size-text">
        Maximum of 50MiB
    </div>

    <?php
    if (count($child_tickets) > 0) {
    ?>
        <div class="childTickets">
            <h2>Child Tickets</h2>
            <table>
                <tr>
                    <th>Ticket ID</th>
                    <th>Ticket Status</th>
                    <th>Ticket Assigned To</th>
                    <th>Ticket Title</th>
                    <th>Ticket Description</th>
                </tr>
                <?php
                foreach ($child_tickets as $child_ticket) {
                ?>
                    <tr>
                        <td><a href="edit_ticket.php?id=<?= $child_ticket['id'] ?>"><?= $child_ticket['id'] ?></a></td>
                        <td><?= $child_ticket['status'] ?></td>
                        <td><?= $child_ticket['employee'] ?></td>
                        <td><?= $child_ticket['name'] ?></td>
                        <td><?= html_entity_decode($child_ticket['description']) ?></td>
                    </tr>
                <?php
                }
                ?>
            </table>

        </div>
    <?php
    }
    ?>
    <?php if ($ticket['notes'] !== null) : ?>
        <h2>Notes</h2>
        <div class="note">
            <table class="ticketsTable">
                <tr>
                    <th>Date</th>
                    <th>Created By</th>
                    <th>Note</th>
                    <th>Time</th>
                </tr>
                <?php
                $total_time = 0; // Initialize total time to 0

                foreach (json_decode($ticket['notes'], true) as $note) :
                    // Hidden notes should only be viewable by admins
                    if (
                        $note['visible_to_client'] == 0 &&
                        $_SESSION['permissions']['is_admin'] != 1
                    )
                        continue;
                    $total_time += $note['time']; // Add note time to total time (doesn't add for non-admins)
                ?>
                    <tr>
                        <td data-cell="Date"><a href="edit_note.php?note_id=<?= $note['note_id'] ?>&ticket_id=<?= $ticket_id ?>">
                                <?php
                                $date_override = $note['date_override'];
                                if ($date_override != null)
                                    echo $date_override . "*";
                                else
                                    echo $note['created'];
                                ?></a></td>
                        <td data-cell="Created By"><?= $note['creator'] ?></td>
                        <td data-cell="Note Message">
                            <?php
                            /*
                                    May want to reference archived tickets in the future,
                                    ignoring for now though.
                                */
                            $ticket_pattern = "/WO#\\d{1,6}/";
                            $asset_tag_pattern = "/BC#\\d{6}/";
                            $note_data = $note['note'];
                            if ($note_data !== null) {
                                $note_data = html_entity_decode($note_data);

                                $ticket_matches = [];
                                $ticket_match_result = preg_match_all($ticket_pattern, $note_data, $ticket_matches);

                                if ($ticket_match_result) {
                                    foreach ($ticket_matches[0] as $match_str) {
                                        $url_ticket_id = substr($match_str, 3);
                                        $url = "<a href=\"edit_ticket.php?id=$url_ticket_id\">$match_str</a>";
                                        $note_data = str_replace($match_str, $url, $note_data);
                                    }
                                }


                                $asset_tag_matches = [];
                                $asset_tag_match_result = preg_match_all($asset_tag_pattern, $note_data, $asset_tag_matches);

                                if ($asset_tag_match_result) {
                                    foreach ($asset_tag_matches[0] as $match_str) {
                                        $barcode = substr($match_str, 3);
                                        // when doing https:// the : kept disappearing, not sure why
                                        // will just let it choose https automatically
                                        $url = "<a href=\"//vault.provo.edu/nac_edit.php?barcode=$barcode\">$match_str</a>";
                                        $note_data = str_replace($match_str, $url, $note_data);
                                    }
                                }

                                echo $note_data;
                            }
                            ?>
                            <span class="note_id">
                                <?php
                                $note_id = $note["note_id"];
                                $visible_to_client = $note['visible_to_client'];
                                if ($note_id !== null) {
                                    $note_id_text =  html_entity_decode($note_id);
                                    echo "<a href=\"edit_note.php?note_id=$note_id&ticket_id=$ticket_id\">Note#: $note_id_text</a><br>";
                                    echo $note['visible_to_client'] ? "Visible to Client" : "Invisible to Client";
                                }
                                ?>
                            </span>
                        </td>
                        <td data-cell="Time Taken"><?= $note['time'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr class="totalTime">
                <td data-cell="Total Time" colspan=4><span>Total Time: </span> <?= $total_time ?></td>

            </tr>
            </table>
        </div>
        <button id="new-note-button">New Note</button><br><br>
        <div id="new-note-form" style="display: none;">
            <h3>Add Note</h3>
            <form method="post" action="add_note_handler.php">
                <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
                <input type="hidden" name="username" value="<?= $_SESSION['username'] ?>">
                <label for="note">Note:</label>
                <textarea id="note" name="note" class="tinyMCEtextarea"></textarea>
                <!-- TODO: Hide the visible to client option for non admins,
                        forms make this a pain because it needs to submit a value if false, system currently relies on not receiving
                        a value to assume no (thus hiding it from client) -->
                <label for="visible_to_client">Visible to Client:</label>
                <input type="checkbox" id="visible_to_client" name="visible_to_client" checked="checked"><br>
                <label for="note_time">Time in Minutes:</label>
                <input id="note_time" name="note_time" type="number"><br>
                <label for="date_override_enable">Date Override:</label>
                <input type="checkbox" id="date_override_enable" name="date_override_enable"><br>
                <input style="display:none;" id="date_override_input" type="datetime-local" name="date_override"><br>
                <input type="submit" value="Add Note">
            </form><br>
            <script src="../../includes/js/jquery-3.7.1.min.js"></script>
            <script>
                $('input[name=date_override_enable]').on('change', function() {
                    if (!this.checked) {
                        $('#date_override_input').hide();
                    } else {
                        $('#date_override_input').show();
                    }
                });
            </script>
        </div>
        <?php
        // Fetch the ticket logs for the current ticket
        $log_query = "SELECT field_name,user_id, old_value, new_value, created_at FROM ticket_logs WHERE ticket_id = ? ORDER BY created_at DESC";
        $log_stmt = mysqli_prepare($database, $log_query);
        mysqli_stmt_bind_param($log_stmt, "i", $ticket_id);
        mysqli_stmt_execute($log_stmt);
        $log_result = mysqli_stmt_get_result($log_stmt);

        // Display the ticket logs in a table
        if (mysqli_num_rows($log_result) > 0) {
        ?>
            <div class="ticket_log">
                <h2>Ticket History</h2>
                <table>
                    <tr>
                        <th>Created At</th>
                        <th>Changed By</th>
                        <th>Changes made</th>
                    </tr>
                    <?php
                    while ($log_row = mysqli_fetch_assoc($log_result)) {
                    ?>
                        <tr>
                            <td data-cell="Date"><?= $log_row['created_at'] ?></td>
                            <td data-cell="Created by"><?= $log_row['user_id'] ?></td>
                            <td data-cell="Change Made">
                                <?php
                                if ($log_row['field_name'] != 'note') {
                                    echo formatFieldName($log_row['field_name']) . ' From: ' . html_entity_decode($log_row['old_value']) . ' To: ' . html_entity_decode($log_row['new_value']);
                                } else {
                                    if ($log_row['old_value'] != null) {
                                        echo 'Note Updated: ' . html_entity_decode($log_row['old_value']) . ' To: ' . html_entity_decode($log_row['new_value']);
                                    } else {
                                        echo 'Note Created: ' . html_entity_decode($log_row['new_value']);
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </table>
            </div>
        <?php
        }
        ?>
</article>
<?php include("../../includes/footer.php"); ?>