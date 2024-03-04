<?php
require_once("block_file.php");

ob_start();
include("ticket_utils.php");
$readonly = session_is_tech() ? '' : 'readonly';
$ticket_id = sanitize_numeric_input($_GET['id']);
// Check if the user is logged in
require("header.php");
if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['can_view_tickets'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to view tickets.';
        exit;
    }
}
require_once('helpdbconnect.php');
require_once("status_popup.php");

// Check if an error message is set
if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}

$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST['flag_ticket'])) {
        $query = <<<STR
        INSERT INTO flagged_tickets
        VALUES (
            (
                SELECT users.id FROM users WHERE users.username = '$username'
            ),
            $ticket_id
        )
        STR;

        $insert_flagged_ticket_result = mysqli_query($database, $query);
        if (!$insert_flagged_ticket_result) {
            die('Error inserting ticket flag status: ' . mysqli_error($database));
        }
    } else if (isset($_POST['unflag_ticket'])) {
        $query = <<<STR
        DELETE FROM flagged_tickets
        WHERE
            flagged_tickets.user_id in (SELECT users.id FROM users WHERE users.username = '$username') AND
            flagged_tickets.ticket_id = $ticket_id
        STR;

        $insert_flagged_ticket_result = mysqli_query($database, $query);
        if (!$insert_flagged_ticket_result) {
            die('Error inserting ticket flag status: ' . mysqli_error($database));
        }
    }

    if (isset($_POST['merge_ticket_id'])) {
        $merge_ticket_id = filter_input(INPUT_POST, 'merge_ticket_id', FILTER_SANITIZE_SPECIAL_CHARS);
        if ($merge_ticket_id != null) {
            // do merging with $ticket_id (source) and $merge_ticket_id (host)

        }
    }
    header("Location: edit_ticket.php?id=$ticket_id");
    exit;
}

$ticket_flagged_query = <<<STR
SELECT user_id, ticket_id FROM flagged_tickets
    WHERE
        ticket_id = $ticket_id AND
        user_id in (SELECT users.id FROM users WHERE users.username = '$username')
STR;

$insert_flagged_ticket_result = mysqli_query($database, $ticket_flagged_query);
if (!$insert_flagged_ticket_result) {
    die('Error getting ticket flag status: ' . mysqli_error($database));
}

$is_ticket_flagged = false;
if (mysqli_num_rows($insert_flagged_ticket_result) > 0) {
    $is_ticket_flagged = true;
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
        'work_hours', notes.work_hours,
        'work_minutes', notes.work_minutes,
        'travel_hours', notes.travel_hours,
        'travel_minutes', notes.travel_minutes,

        'visible_to_client', notes.visible_to_client,
        'date_override', notes.date_override
    )
    ORDER BY (
        CASE WHEN notes.date_override IS NULL THEN
            notes.created ELSE
            notes.date_override
        END
    )
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

//fetch child tickets
$child_ticket_query = "SELECT * FROM tickets WHERE parent_ticket = ?";
$child_ticket_stmt = $database->prepare($child_ticket_query);
$child_ticket_stmt->bind_param("i", $ticket_id);
$child_ticket_stmt->execute();

$child_ticket_result = $child_ticket_stmt->get_result();
$child_tickets = $child_ticket_result->fetch_all(MYSQLI_ASSOC);

$clientFirstName = "";
$clientLastName = "";
if (isset($ticket["client"])) {
    $result = get_client_name($ticket["client"]);
    $clientFirstName = $result['firstname'];
    $clientLastName = $result['lastname'];
}
?>
<article id="ticketWrapper">
    <div id="ticket-title-container">
        <h1 id="ticket-title">Ticket #<?= $ticket['id'] ?></h1>
    </div>
    <br>
    <br>

    <div id="search-for-client">
        <h2>Client Search:</h2>
        <form id="search-form" method="post">
            <label for="firstname">First Name:</label>
            <input type="text" id="firstname" name="firstname">
            <label for="lastname">Last Name:</label>
            <input type="text" id="lastname" name="lastname">
            <input type="submit" value="Search">
        </form>

        <div id="search-results"></div>
    </div>
    <!-- Form for updating ticket information -->
    <div class="right">
        <button class="new-note-button button">New Note</button>
    </div>
    <form id="updateTicketForm" method="POST" action="update_ticket.php">
        <!-- Add a submit button to update the information -->
        <input id="green-button" type="submit" value="Update Ticket">
        <div>
            Send Client Email on Update:<input type="checkbox" name="send_emails" value="send_emails" checked>
        </div>
        <div>
            Send CC/BCC Emails on Update:<input type="checkbox" name="send_cc_bcc_emails" value="send_cc_bcc_emails" checked>
        </div>
        <div class="ticketGrid">
            <input type="hidden" name="ticket_create_date" value="<?= $ticket['created'] ?>">
            <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
            <input type="hidden" name="madeby" value="<?= $_SESSION['username'] ?>">
            <input type="hidden" id="client" name="client" value="<?= $ticket['client'] ?>">
            <?php
            // If the user is not a tech, display read only form fields if is client
            if ($readonly) {
            ?>
                <div class="readonlyClient">
                    <span>Client: </span> <span id="client-display"><?= $clientFirstName . " " . $clientLastName . " (" . $ticket['client'] . ")" ?></span>
                </div>
            <?php

            } else {
            ?>
                <div class="currentClient">
                    <span>Client: </span> <span id="client-display"><?= $clientFirstName . " " . $clientLastName . " (" . $ticket['client'] . ")" ?></span> <a>Change Client</a>
                </div>
            <?php
            }
            ?>

            <div>
                <span>Created:</span> <?= $ticket['created'] ?>
            </div>
            <div>
                <span>Last Updated:</span> <?= $ticket['last_updated'] ?>
            </div>
            <div>

                <span>Current Due Date:</span> <?= $ticket['due_date'] ?>
                <input type="hidden" id="due_date" name="due_date" value="<?= $ticket['due_date'] ?>">
            </div>


            <?php
            // If the user is not a tech, display read only form fields if is client
            if ($readonly) {
            ?>
                <div>
                    <span>Assigned Tech:</span> <?= $ticket['employee'] ?>
                </div>
                <input type="hidden" id="employee" name="employee" value="<?= $ticket['employee'] ?>">

                <div>
                    <span>Location:</span> <?= $ticket['location'] ?>
                </div>
                <input type="hidden" id="location" name="location" value="<?= $ticket['location'] ?>">

                <div>
                    <span>Request Type:</span> <?= $ticket['request_type_id'] ?>
                </div>
                <input type="hidden" id="request_type" name="request_type" value="<?= $ticket['location'] ?>">

                <div>
                    <span>Current Status:</span> <?= $ticket['status'] ?>
                </div>
                <input type="hidden" id="status" name="status" value="<?= $ticket['status'] ?>">

                <div>
                    <span>Priority:</span> <?= $ticket['priority'] ?>
                </div>
                <input type="hidden" id="priority" name="priority" value="<?= $ticket['priority'] ?>">

                <input type="hidden" id="parent_ticket" name="parent_ticket" value="<?= $ticket['parent_ticket'] == 0 ? '' : $ticket['parent_ticket'] ?>">
            <?php

            } else {
                // Display Fields that tech can edit
            ?>
                <div> <label for="employee">Assigned Tech:</label>
                    <select id="employee" name="employee">
                        <option value="unassigned">Unassigned</option>
                        <?php foreach ($techusernames as $username) : ?>
                            <option value="<?= $username ?>" <?= $ticket['employee'] === $username ? 'selected' : '' ?>><?= $username ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="location">Department/Location:</label>
                    <select id="location" name="location">
                        <?php
                        // Query the locations table to get the departments
                        $department_query = "SELECT sitenumber, location_name FROM locations WHERE is_department = TRUE ORDER BY location_name ASC";
                        $department_result = mysqli_query($database, $department_query);

                        // Create a "Department" optgroup and create an option for each department
                        echo '<optgroup label="Department">';
                        while ($locations = mysqli_fetch_assoc($department_result)) {
                            $selected = '';
                            if ($locations['sitenumber'] == $ticket['location']) {
                                $selected = 'selected';
                            }
                            echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
                        }
                        echo '</optgroup>';

                        // Query the locations table to get the locations
                        $location_query = "SELECT sitenumber, location_name FROM locations WHERE is_department = FALSE ORDER BY location_name ASC";
                        $location_result = mysqli_query($database, $location_query);

                        // Create a "Location" optgroup and create an option for each location
                        echo '<optgroup label="Location">';
                        while ($locations = mysqli_fetch_assoc($location_result)) {
                            $selected = '';
                            if ($locations['sitenumber'] == $ticket['location']) {
                                $selected = 'selected';
                            }
                            echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
                        }
                        echo '</optgroup>';
                        ?>
                    </select>
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
                    <input type="number" id="parent_ticket" name="parent_ticket" value="<?= $ticket['parent_ticket'] == 0 ? '' : $ticket['parent_ticket'] ?>">
                </div>
            <?php
            }
            ?>


            <!-- Fields that are editable by client -->

            <div>
                <label for="room">Room:</label>
                <input type="text" id="room" name="room" value="<?= $ticket['room'] ?>">
            </div>
            <div>
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" value="<?= $ticket['phone'] ?>">
            </div>

            <?php if ($_SESSION['permissions']['is_supervisor'] != 0 || $_SESSION['permissions']['is_admin'] != 0 || $ticket['priority'] == 30 && $ticket['client'] == $ticket['employee']) : ?>
                <div>
                    <label for="due_date">Modify Due Date:</label>
                    <input type="date" id="due_date" name="due_date" value="<?= $ticket['due_date'] ?>">
                </div>
            <?php endif; ?>


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
                <label for="ticket_name">Ticket Title:</label>
                <input type="text" id="ticket_name" name="ticket_name" value="<?= $ticket['name'] ?>">
            </div>
            <label for="description" class="heading2">Request Detail:</label>
            <div class="ticket-description">
                <?php
                $ticket_pattern = "/WO#\\d{1,6}/";
                $archived_ticket_pattern = "/WO#A-\\d{1,6}/";
                $asset_tag_pattern = "/BC#\\d{6}/";
                $request_detail = $ticket['description'];
                $ticket_matches = [];
                $ticket_match_result = preg_match_all($ticket_pattern, $request_detail, $ticket_matches, PREG_OFFSET_CAPTURE);

                if ($ticket_match_result) {
                    foreach ($ticket_matches[0] as $match) {
                        $match_str = $match[0];
                        $url_ticket_id = substr($match_str, 3);
                        $url = "<a target=\"_blank\" href=\"edit_ticket.php?id=$url_ticket_id&nr=1\">$match_str</a>";
                        $request_detail = str_replace($match_str, $url, $request_detail);
                    }
                }

                $archived_ticket_matches = [];
                $archived_ticket_match_result = preg_match_all($archived_ticket_pattern, $request_detail, $archived_ticket_matches, PREG_OFFSET_CAPTURE);

                if ($archived_ticket_match_result) {
                    foreach ($archived_ticket_matches[0] as $match) {
                        $match_str = $match[0];
                        $url_ticket_id = substr($match_str, 3);
                        $url = "<a target=\"_blank\" href=\"archived_ticket_view.php?id=$url_ticket_id\">$match_str</a>";
                        $request_detail = str_replace($match_str, $url, $request_detail);
                    }
                }


                $asset_tag_matches = [];
                $asset_tag_match_result = preg_match_all($asset_tag_pattern, $request_detail, $asset_tag_matches, PREG_OFFSET_CAPTURE);

                if ($asset_tag_match_result) {
                    foreach ($asset_tag_matches[0] as $match) {
                        $match_str = $match[0];
                        if ($match_str[0] == 'B')
                            $barcode = substr($match_str, 3);
                        else
                            $barcode = $match_str;

                        // when doing https:// the : kept disappearing, not sure why
                        // will just let it choose https automatically
                        $url = "<a target=\"_blank\" href=\"//vault.provo.edu/nac_edit.php?barcode=$barcode\">$match_str</a>";

                        $request_detail = str_replace($match_str, $url, $request_detail);
                    }
                }
                echo html_entity_decode($request_detail);

                ?>
                <?php
                if ($_SESSION['permissions']['is_admin'] == 1) {
                ?>
                    <button id="edit-description-button" type="button">Edit Request Detail</button>
                <?php
                }
                ?>

            </div>

            <div id="edit-description-form" style="display: none;">
                <textarea id="description" name="description" class="tinyMCEtextarea"><?= $ticket['description'] ?></textarea>
            </div>
        </div>

    </form>

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
        <div id="maximum-file-size-text">
            Maximum of 50MB
        </div>
    </div>


    <?php
    if (count($child_tickets) > 0) {
    ?>
        <div class="childTickets">
            <h2>Sub Tasks / Child Tickets</h2>
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
                        <td data-cell="Ticket ID"><a href="edit_ticket.php?id=<?= $child_ticket['id'] ?>"><?= $child_ticket['id'] ?></a></td>
                        <td data-cell="Status"><?= $child_ticket['status'] ?></td>
                        <td data-cell="Tech"><?= $child_ticket['employee'] ?></td>
                        <td data-cell="Ticket Title"><?= $child_ticket['name'] ?></td>
                        <td data-cell="Request Detail"><?= html_entity_decode($child_ticket['description']) ?></td>
                    </tr>
                <?php
                }
                ?>
            </table>

        </div>
    <?php
    }
    ?>
    <!-- Loop through the notes and display them -->
    <?php if ($ticket['notes'] !== null) : ?>



        <h2>Notes</h2>
        <button class="new-note-button button">New Note</button><br>
        <div id="note-table" class="note">
            <table class="ticketsTable">
                <tr>
                    <th>Date</th>
                    <th>Created By</th>
                    <th>Note</th>
                    <th class="timeColumn">Time</th>
                </tr>
                <?php
                $total_minutes = 0;
                $total_hours = 0;

                foreach (json_decode($ticket['notes'], true) as $note) :
                    // Hidden notes should only be viewable by admins
                    if (
                        $note['visible_to_client'] == 0 &&
                        !$_SESSION['permissions']['is_tech']
                    )
                        continue;

                    // Add the total time for this note to the total time for all notes
                    $total_minutes += $note['work_minutes'] + $note['travel_minutes'];
                    $total_hours += $note['work_hours'] + $note['travel_hours'];
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
                        <td class="ticket_note" data-cell="Note Message">
                            <?php
                            $ticket_pattern = "/WO#\\d{1,6}/";
                            $archived_ticket_pattern = "/WO#A-\\d{1,6}/";
                            $asset_tag_pattern = "/BC#\\d{6}/";
                            $note_data = $note['note'];
                            if ($note_data !== null) {
                                $note_data = html_entity_decode($note_data);

                                $ticket_matches = [];
                                $ticket_match_result = preg_match_all($ticket_pattern, $note_data, $ticket_matches, PREG_OFFSET_CAPTURE);

                                if ($ticket_match_result) {
                                    foreach ($ticket_matches[0] as $match) {
                                        $match_str = $match[0];
                                        $url_ticket_id = substr($match_str, 3);
                                        $url = "<a target=\"_blank\" href=\"edit_ticket.php?id=$url_ticket_id&nr=1\">$match_str</a>";
                                        $note_data = str_replace($match_str, $url, $note_data);
                                    }
                                }

                                $archived_ticket_matches = [];
                                $archived_ticket_match_result = preg_match_all($archived_ticket_pattern, $note_data, $archived_ticket_matches, PREG_OFFSET_CAPTURE);

                                if ($archived_ticket_match_result) {
                                    foreach ($archived_ticket_matches[0] as $match) {
                                        $match_str = $match[0];
                                        $url_ticket_id = substr($match_str, 3);
                                        $url = "<a target=\"_blank\" href=\"archived_ticket_view.php?id=$url_ticket_id\">$match_str</a>";
                                        $note_data = str_replace($match_str, $url, $note_data);
                                    }
                                }


                                $asset_tag_matches = [];
                                $asset_tag_match_result = preg_match_all($asset_tag_pattern, $note_data, $asset_tag_matches, PREG_OFFSET_CAPTURE);

                                if ($asset_tag_match_result) {
                                    foreach ($asset_tag_matches[0] as $match) {
                                        $match_str = $match[0];
                                        if ($match_str[0] == 'B')
                                            $barcode = substr($match_str, 3);
                                        else
                                            $barcode = $match_str;

                                        // when doing https:// the : kept disappearing, not sure why
                                        // will just let it choose https automatically
                                        $url = "<a target=\"_blank\" href=\"//vault.provo.edu/nac_edit.php?barcode=$barcode\">$match_str</a>";

                                        $note_data = str_replace($match_str, $url, $note_data);
                                    }
                                }
                                /*
                                $asset_tag_alt_matches = [];
                                $asset_tag_alt_match_result = preg_match_all($asset_tag_pattern_alt, $note_data, $asset_tag_alt_matches);

                                if ($asset_tag_alt_match_result) {
                                    foreach ($asset_tag_alt_matches[0] as $match_str) {
                                        $url = "<a target=\"_blank\" href=\"//vault.provo.edu/nac_edit.php?barcode=$match_str\">$match_str</a>";
                                        $note_data = str_replace($match_str, $url, $note_data);
                                    }
                                }*/
                            ?>
                                <span <?php
                                        $note_creator = $note["creator"];
                                        if (!user_is_tech($note_creator)) {
                                            echo 'class="note-content nonTech"';
                                        } else if ($note['visible_to_client'] == 0) {
                                            echo 'class="note-content notClientVisible"';
                                        } else {
                                            echo 'class="note-content clientVisible"';
                                        } ?>>
                                    <?php echo html_entity_decode($note_data); ?>
                                </span>
                            <?php
                            }
                            ?>
                            <span class="note_id">
                                <?php
                                $note_id = $note["note_id"];
                                $visible_to_client = $note['visible_to_client'];
                                if ($note_id !== null) {
                                    $note_id_text =  html_entity_decode($note_id);
                                    echo "<a href=\"edit_note.php?note_id=$note_id&ticket_id=$ticket_id\">Note#: $note_id_text</a>";
                                    echo $note['visible_to_client'] ? "Visible to Client" : "Invisible to Client";
                                    echo '<span class="created_date">' . $note['created'] . '</span>';
                                    echo '<span class="time_since_last_note"></span>';
                                }
                                ?>
                            </span>
                        </td>

                        <td data-cell="Time Taken">
                            <?php
                            displayTime($note, 'work');
                            displayTime($note, 'travel');
                            $totalHours = $note['work_hours'] + $note['travel_hours'];
                            $totalMinutes = $note['work_minutes'] + $note['travel_minutes'];
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr class="totalTime">
                <td data-cell="Total Time" colspan=4>
                    <?php
                    displayTotalTime($total_hours, $total_minutes);
                    ?>
                </td>
            </tr>
            </table>
        </div>
        <button class="new-note-button" id="new-note-button">New Note</button>
        <div id="new-note-form-background">
            <div id="new-note-form" style="display: none;">
                <div id="new-note-form-header"><span id="new-note-form-close">&times;</span></div>
                <h3>New Note</h3>
                <form id="note-submit" method="post" action="add_note_handler.php">
                    <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
                    <input type="hidden" name="username" value="<?= $_SESSION['username'] ?>">
                    <div>
                        <label for="note">Note:</label>
                        <textarea id="note" name="note" class="tinyMCEtextarea"></textarea>
                    </div>
                    <?php
                    if (session_is_tech()) {
                    ?>
                        <div>
                            <label for="visible_to_client">Visible to Client:</label>
                            <input type="checkbox" id="visible_to_client" name="visible_to_client" checked="checked">
                        </div>
                        <h4>Work Time</h4>
                        <div>
                            <label for="work_hours">Hours:</label>
                            <input id="work_hours" name="work_hours" type="number" value="0" required>

                            <label for="work_minutes">Minutes:</label>
                            <input id="work_minutes" name="work_minutes" type="number" value="0" required>
                        </div>
                        <h4>Travel Time</h4>
                        <div>
                            <label for="travel_hours">Hours:</label>
                            <input id="travel_hours" name="travel_hours" type="number" value="0" required>

                            <label for="travel_minutes">Minutes:</label>
                            <input id="travel_minutes" name="travel_minutes" type="number" value="0" required>
                        </div>

                        <div>
                            <label for="total_time">Total Time in Minutes:</label>
                            <input id="total_time" name="total_time" type="number" readonly>
                        </div>
                        <div>
                            <label for="date_override_enable">Date Override:</label>
                            <input type="checkbox" id="date_override_enable" name="date_override_enable">
                            <input style="display:none;" id="date_override_input" type="datetime-local" name="date_override">
                        </div>
                    <?php
                    } else {
                    ?>
                        <input type="hidden" id="visible_to_client" name="visible_to_client" value="1">
                        <input id="total_time" name="total_time" type="hidden" value="0">
                        <input id="travel_minutes" name="travel_minutes" type="hidden" value="0" required>
                        <input id="travel_hours" name="travel_hours" type="hidden" value="0" required>
                        <input id="work_minutes" name="work_minutes" type="hidden" value="0" required>
                        <input id="work_hours" name="work_hours" type="hidden" value="0" required>
                    <?php
                    }
                    ?>
                    <br>
                    <input type="submit" value="Submit Note">
                </form>
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
        </div>
        <?php
        // Fetch the ticket logs for the current ticket
        $log_query = "SELECT field_name,user_id, old_value, new_value, created_at FROM ticket_logs WHERE ticket_id = ? ORDER BY created_at DESC";
        $log_stmt = mysqli_prepare($database, $log_query);
        mysqli_stmt_bind_param($log_stmt, "i", $ticket_id);
        mysqli_stmt_execute($log_stmt);
        $log_result = mysqli_stmt_get_result($log_stmt);

        // Display the ticket logs in a table
        if (session_is_tech() && mysqli_num_rows($log_result) > 0) {
        ?>
            <div class="ticket_log">
                <h2>Ticket History</h2>
                <p id="ticket-history-status">(collapsed)</p>
                <table id="ticket-history">
                    <tr class="ticket-history-header">
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
                            <td class="ticket_note" data-cell="Change Made">
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
        <br>
        <?php
        if ($is_ticket_flagged) :
        ?>
            <form id="flag-form" method="post">
                <input type="submit" name="unflag_ticket" value="Unflag ticket" class="right">
            </form>
        <?php else : ?>
            <form id="flag-form" method="post">
                <input type="submit" name="flag_ticket" value="Flag ticket" class="right">
            </form>
        <?php endif; ?>

        <?php if ($_SESSION['permissions']['is_tech']) : ?>
            <br>
            <form id="merge-form" method="post" action="merge_tickets_handler.php">
                <label for="merge_ticket_id">Merge this ticket into:</label>
                <input type="hidden" name="ticket_id_source" value="<?= $ticket_id ?>">
                <input type="text" name="ticket_id_host" value=""><br>
                <input type="submit" value="Merge">
            </form>
        <?php endif; ?>
</article>
<script>
    // Make links in note content open in new tab
    $('.note-content a').attr('target', '_blank');

    // Toggle ticket history visibility to closed on page load
    $('#ticket-history .ticket-history-header').nextUntil('tr.header').toggle();

    // Toggle ticket history visibility when clicked
    $('#ticket-history .ticket-history-header').click(function() {
        $(this).nextUntil('tr.header').toggle();
        const ticketHistoryStatus = document.getElementById("ticket-history-status");

        let ticketHistoryStatusText = ticketHistoryStatus.textContent;

        if (ticketHistoryStatusText == "(collapsed)")
            ticketHistoryStatusText = "(expanded)"
        else if (ticketHistoryStatusText == "(expanded)")
            ticketHistoryStatusText = "(collapsed)"

        ticketHistoryStatus.textContent = ticketHistoryStatusText;
    });

    const title = document.getElementById("ticket-title");
    title.onclick = function() {
        document.execCommand("copy");
    }
    title.addEventListener("copy", function(event) {
        event.preventDefault();
        if (event.clipboardData) {
            event.clipboardData.setData("text/plain", window.location.host + "/controllers/tickets/edit_ticket.php?id=" + <?= $ticket_id ?>);
            console.log(event.clipboardData.getData("text"))
        }
    });
</script>
<?php if ($_SESSION['permissions']['is_tech']) : ?>
    <script>
        //calc time on the fly
        document.querySelectorAll('#work_hours, #work_minutes, #travel_hours, #travel_minutes').forEach(function(el) {
            el.addEventListener('input', function() {
                const workHours = parseInt(document.getElementById('work_hours').value) || 0;
                const workMinutes = parseInt(document.getElementById('work_minutes').value) || 0;
                const travelHours = parseInt(document.getElementById('travel_hours').value) || 0;
                const travelMinutes = parseInt(document.getElementById('travel_minutes').value) || 0;

                const totalTime = (workHours + travelHours) * 60 + workMinutes + travelMinutes;

                document.getElementById('total_time').value = totalTime;
            });
        });

        // add alert if no time is entered
        $("#note-submit").on("submit", function(evt) {
            let fields = ['work_hours', 'work_minutes', 'travel_hours', 'travel_minutes'];
            let allZero = fields.every(function(field) {
                return parseInt(document.getElementById(field).value, 10) === 0;
            });

            const note_content = tinymce.activeEditor.getContent("note");
            console.log(note_content);
            if (allZero) {
                alert('Please enter a value greater than 0 for at least one of the time fields.');
                evt.preventDefault(); // Prevent the form submission
            } else if (!note_content) {
                alert('Please enter some note content');
                evt.preventDefault();
            }
        });

        function updateTimeSinceLastNote() {
            // Loop over each element with the class 'created_date'
            $('.created_date').each(function() {
                // Get the created date from the element
                var createdDate = new Date($(this).text());

                // Calculate the time since the last note
                var currentTime = new Date();
                var diffInMinutes = Math.round((currentTime - createdDate) / 60000); // in minutes

                // Calculate the new time since the last note
                var newTimeSinceLastNote;
                if (diffInMinutes < 60) {
                    newTimeSinceLastNote = diffInMinutes + ' minutes ago';
                } else if (diffInMinutes < 24 * 60) {
                    var diffInHours = Math.round(diffInMinutes / 60);
                    newTimeSinceLastNote = diffInHours + ' hours ago';
                } else {
                    var diffInDays = Math.round(diffInMinutes / (24 * 60));
                    newTimeSinceLastNote = diffInDays + ' days ago';
                }

                // Get the current time since the last note
                var currentTimeSinceLastNote = $(this).next('.time_since_last_note').text();

                // Only update the DOM if the new time is different from the current time
                if (newTimeSinceLastNote !== currentTimeSinceLastNote) {
                    $(this).next('.time_since_last_note').text(newTimeSinceLastNote);
                }
            });
        }

        // Run the function when the page loads
        updateTimeSinceLastNote();

        // Then run the function every 60 seconds
        setInterval(updateTimeSinceLastNote, 60000); // 60000 milliseconds
    </script>
<?php endif; ?>
<?php include("footer.php"); ?>
<script>
    function split(val) {
        return val.split(/,\s*/);
    }

    function extractLast(term) {
        return split(term).pop();
    }

    $("#cc_emails").on("input", function() {
        const new_value = extractLast($(this).val());
        $("#cc_emails").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/user_matches_ldap.php",
                    method: "GET",
                    data: {email: new_value},
                    success: function(data, textStatus, xhr) {
                        let mappedResults = $.map(data, function (item) {
                            let itemLocation = item.location ? item.location : "unknown";
                            return $.extend(item, { label: item.firstName + ' ' + item.lastName + ' (' + itemLocation + ')', value: item.email });
                        });
                        response(mappedResults);
                    },
                    error: function() {
                        alert("Error: Autocomplete AJAX call failed");
                    }
                });
            },
            minLength: 3,
            search: function() {
                const term = extractLast(this.value);
                if (term.length < 1) {
                    return false;
                }
            },
            focus: function () {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                let terms = split(this.value);

                terms.pop();
                terms.push(ui.item.value);
                terms.push("");

                this.value = terms.join(",");
                return false;
            }
        });
    });

    $("#bcc_emails").on("input", function() {
        const new_value = extractLast($(this).val());
        console.log("running");
        $("#bcc_emails").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/user_matches_ldap.php",
                    method: "GET",
                    data: {email: new_value},
                    success: function(data, textStatus, xhr) {
                        let mappedResults = $.map(data, function (item) {
                            let itemLocation = item.location ? item.location : "unknown";
                            return $.extend(item, { label: item.firstName + ' ' + item.lastName + ' (' + itemLocation + ')', value: item.email });
                        });
                        response(mappedResults);
                    },
                    error: function() {
                        alert("Error: Autocomplete AJAX call failed");
                    }
                });
            },
            minLength: 3,
            search: function() {
                const term = extractLast(this.value);
                if (term.length < 1) {
                    return false;
                }
            },
            focus: function () {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                let terms = split(this.value);

                terms.pop();
                terms.push(ui.item.value);
                terms.push("");

                this.value = terms.join(",");
                return false;
            }
        });
    });

</script>