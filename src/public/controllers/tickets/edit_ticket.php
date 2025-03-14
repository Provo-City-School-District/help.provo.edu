<?php
require_once("block_file.php");
require_once("sanitization_utils.php");
require_once("email_utils.php");

$_SESSION["user_notifications"] = [];

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

        // TODO: Fix intern viewing permissions (Right now all intern accounts have can_view_tickets=1. Eventually I think we want that off)
        echo 'You do not have permission to view tickets.';
        exit;
    }
}
require_once('helpdbconnect.php');
require_once("status_popup.php");

$ticket_exists_res = HelpDB::get()->execute_query("SELECT 1 FROM help.tickets WHERE id = ?", [$ticket_id]);
if (!$ticket_exists_res) {
    log_app(LOG_ERR, "Failed to check existence of $ticket_id");
}
$ticket_exists = $ticket_exists_res->num_rows > 0;

if (!$ticket_exists) {
    echo "Ticket $ticket_id does not exist";
    exit;
}

$username = $_SESSION['username'];
$ticket_shared_usernames = [];

$is_ticket_shared_res = HelpDB::get()->execute_query("SELECT username FROM help.users WHERE active_ticket = ?", [$ticket_id]);
if (!$is_ticket_shared_res) {
    log_app(LOG_ERR, "Failed to get active_ticket status for ticket $ticket_id");
}

if ($is_ticket_shared_res->num_rows > 0) {

    while ($row = $is_ticket_shared_res->fetch_assoc()) {
        $tmp_username = $row["username"];
        if ($tmp_username != $username)
            $ticket_shared_usernames[] = $tmp_username;
    }
}

// Update active ticket for user
$active_ticket_res = HelpDB::get()->execute_query("UPDATE help.users SET active_ticket = ?, active_ticket_updated = NOW() WHERE username = ?", [$ticket_id, $username]);
if (!$active_ticket_res) {
    log_app(LOG_ERR, "Failed to update active_ticket for user $username on ticket $ticket_id");
}

if (count($ticket_shared_usernames) > 0) {
    $shared_ticket_username = $ticket_shared_usernames[0];
    $msg_str = "This ticket is currently being edited by ";
    $user_count = count($ticket_shared_usernames);

    for ($i = 0; $i < $user_count; $i++) {
        $user_name = get_client_name($ticket_shared_usernames[$i]);
        $firstname = $user_name['firstname'];
        $lastname = $user_name['lastname'];

        if ($i == $user_count - 1)
            $msg_str .= "{$firstname} {$lastname}";
        else
            $msg_str .= "{$firstname} {$lastname}, ";
    }

    $status = [
        "message" => $msg_str,
        "type" => "info"
    ];
    $_SESSION["user_notifications"][] = $status;
}

// New notifications API
if (isset($_SESSION['user_notifications'])) {
    foreach ($_SESSION['user_notifications'] as $notif) {
        $status_popup = new StatusPopup($notif["message"], StatusPopupType::fromString($notif["type"]));
        echo $status_popup;
    }

    unset($_SESSION['user_notifications']);
}

if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (isset($_POST['flag_ticket'])) {
        $query = <<<STR
        INSERT INTO flagged_tickets
        VALUES (
            (
                SELECT users.id FROM users WHERE users.username = ?
            ),
            ?
        )
        STR;

        $insert_flagged_ticket_result = HelpDB::get()->execute_query($query, [$username, $ticket_id]);
        if (!$insert_flagged_ticket_result) {
            die('Error inserting ticket flag status: ' . mysqli_error(HelpDB::get()));
        }
    } else if (isset($_POST['unflag_ticket'])) {
        $query = <<<STR
        DELETE FROM flagged_tickets
        WHERE
            flagged_tickets.user_id in (SELECT users.id FROM users WHERE users.username = ?) AND
            flagged_tickets.ticket_id = ?
        STR;

        $insert_flagged_ticket_result = HelpDB::get()->execute_query($query, [$username, $ticket_id]);
        if (!$insert_flagged_ticket_result) {
            die('Error inserting ticket flag status: ' . mysqli_error(HelpDB::get()));
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
        ticket_id = ? AND
        user_id in (SELECT users.id FROM users WHERE users.username = ?)
STR;

$insert_flagged_ticket_result = HelpDB::get()->execute_query($ticket_flagged_query, [$ticket_id, $username]);
if (!$insert_flagged_ticket_result) {
    die('Error getting ticket flag status: ' . mysqli_error(HelpDB::get()));
}

$is_ticket_flagged = false;
if (mysqli_num_rows($insert_flagged_ticket_result) > 0) {
    $is_ticket_flagged = true;
}

// Note Order
$note_order = "ASC";

// Note count
$note_count = isset($_SESSION['note_count']) ? $_SESSION['note_count'] : 5;


// Query the ticket by ID and all notes for that ID
$query = "SELECT
tickets.id,
tickets.client,
tickets.employee,
tickets.location,
tickets.department,
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
tickets.send_client_email,
tickets.send_tech_email,
tickets.send_cc_emails,
tickets.send_bcc_emails,
tickets.intern_visible,
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
    ) $note_order
) AS notes
FROM
tickets
LEFT JOIN
notes
ON
tickets.id = notes.linked_id
WHERE
tickets.id = ?
GROUP BY
tickets.id
";
$result = HelpDB::get()->execute_query($query, [$ticket_id]);

// Check if the query was successful
if (!$result) {
    die('Error: ' . mysqli_error(HelpDB::get()));
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

// Define the list of Department locations
//this is to fix older tickets prior to the separation of the department and location fields
$dep_locations = [
    1700,
    1510,
    1897,
    1540,
    1100,
    881100,
    1560,
    1350,
    1300,
    1310,
    1360,
    1370,
    1200
];

// Check if the ticket location is in the list and set the department variable
if (in_array($ticket['location'], $dep_locations)) {
    $ticket['department'] = $ticket['location'];
}

// Fetch the current user's department
$user_department_query = "SELECT department FROM user_settings WHERE user_id = (SELECT id FROM users WHERE username = ?)";
$user_department_result = HelpDB::get()->execute_query($user_department_query, [$username]);

if (!$user_department_result) {
    die('Error fetching user department: ' . mysqli_error(HelpDB::get()));
}

$user_department_row = mysqli_fetch_assoc($user_department_result);
$user_department = $user_department_row['department'];

// Check if the user has permission to see all techs
$can_see_all_techs = $_SESSION['permissions']['can_see_all_techs'] ?? 0;


// Fetch the list of tech usernames from the users table
if ($can_see_all_techs) {
    // Fetch all tech usernames
    $tech_usernames_query = "
        SELECT u.username, us.is_tech 
        FROM users u
        LEFT JOIN user_settings us ON u.id = us.user_id
        WHERE us.is_tech = 1
        ORDER BY u.username ASC
    ";
    $tech_usernames_result = HelpDB::get()->execute_query($tech_usernames_query);
} else {
    // Fetch tech usernames in the same department
    $tech_usernames_query = "
        SELECT u.username, us.is_tech 
        FROM users u
        LEFT JOIN user_settings us ON u.id = us.user_id
        WHERE us.is_tech = 1 AND us.department = ?
        ORDER BY u.username ASC
    ";
    $tech_usernames_result = HelpDB::get()->execute_query($tech_usernames_query, [$user_department]);
}

if (!$tech_usernames_result) {
    die('Error fetching tech usernames: ' . mysqli_error(HelpDB::get()));
}

// Store the tech usernames in an array
$tech_usernames = [];
while ($username_row = mysqli_fetch_assoc($tech_usernames_result)) {
    if ($username_row['is_tech'] == 1) {
        $tech_usernames[] = $username_row['username'];
    }
}

//fetch child tickets
$child_ticket_query = "SELECT * FROM tickets WHERE parent_ticket = ?";
$child_ticket_stmt = HelpDB::get()->prepare($child_ticket_query);
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

$show_quick_switch_buttons = false;
// If ticket assigned tech is currently logged in user's
if (strtolower($ticket["employee"]) == strtolower($username)) {
    $show_quick_switch_buttons = true;

    $assigned_tickets_result = HelpDB::get()->execute_query("SELECT id FROM tickets WHERE (status NOT IN ('Closed', 'Resolved') AND employee = ?) ORDER BY id ASC", [$username]);

    $assigned_ticket_ids = [];
    while ($row = $assigned_tickets_result->fetch_assoc()) {
        $assigned_ticket_ids[] = $row["id"];
    }

    $max_idx = count($assigned_ticket_ids) - 1;

    $left_idx = array_search($ticket_id, $assigned_ticket_ids) - 1;
    $right_idx = array_search($ticket_id, $assigned_ticket_ids) + 1;

    if ($left_idx < 0) {
        $left_idx = $max_idx;
    }

    if ($right_idx > $max_idx) {
        $right_idx = 0;
    }

    $left_ticket_id = $assigned_ticket_ids[$left_idx];
    $right_ticket_id = $assigned_ticket_ids[$right_idx];
}

$alerts_res = HelpDB::get()->execute_query("SELECT * FROM alerts WHERE alerts.ticket_id = ?", [$ticket_id]);
$alert_data = [];
while ($row = $alerts_res->fetch_assoc()) {
    $alert_data[] = $row;
}


function get_attachment_data(string $file_path)
{
    $real_user_path = realpath(from_root("/../uploads/$file_path"));
    $real_base_path = realpath(from_root("/../uploads/")) . DIRECTORY_SEPARATOR;


    // Validate that the file is being accessed in ${PROJECT_ROOT}/uploads
    if ($real_user_path === false || (substr($real_user_path, 0, strlen($real_base_path)) != $real_base_path)) {
        return null;
    }

    $data = file_get_contents($real_user_path);
    $content_type = mime_content_type($real_user_path);

    $b64_data = base64_encode($data);
    $base64 = "data:image/$content_type;base64,$b64_data";

    return $base64;
}
//parse note information
$notes = json_decode($ticket['notes'], true);
$total_note_count = count($notes);
// Check if notes array is empty or contains only null values
$hasNotes = !empty($notes) && array_filter($notes, function ($note) {
    return !is_null($note['note']);
});

$user_id = get_id_for_user($username);

$insert_viewed_query = <<<STR
    INSERT INTO ticket_viewed (user_id, ticket_id, last_viewed) VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE last_viewed = NOW()
STR;
$insert_viewed_status = HelpDB::get()->execute_query($insert_viewed_query, [$user_id, $ticket_id]);

?>
<div class="alerts_wrapper">
    <?php foreach ($alert_data as $alert) : ?>
        <p class="<?= $alert["alert_level"] ?>">
            <a><?= $alert["message"] ?></a>
            <a class="close-alert" href="/controllers/tickets/alert_delete.php?id=<?= $alert["id"] ?>">&times;</a>
        </p>
    <?php endforeach; ?>
</div>
<article id="ticketWrapper">
    <div id="ticket-title-container">
        <?php if ($show_quick_switch_buttons) : ?>
            <a href="/controllers/tickets/edit_ticket.php?id=<?= $left_ticket_id ?>">&larr;</a>
        <? endif; ?>
        Ticket <?= $ticket['id'] ?>
        <?php if ($show_quick_switch_buttons) : ?>
            <a href="/controllers/tickets/edit_ticket.php?id=<?= $right_ticket_id ?>">&rarr;</a>
        <? endif; ?>
    </div>
    <?php
    if (isset($ticket['parent_ticket']) && $ticket['parent_ticket'] != null) {

    ?>
        <div class="parentTicket">
            <p>Parent Ticket: <a href="edit_ticket.php?id=<?= $ticket['parent_ticket'] ?>"><?= $ticket['parent_ticket'] ?></a></p>
        </div>
    <?php
    }
    ?>
    <div id="search-for-client">
        <h2>Client Search:</h2>
        <form id="search-form" method="post">
            <label for="firstname">First Name:</label>
            <input type="text" id="firstname" name="firstname">
            <label for="lastname">Last Name:</label>
            <input type="text" id="lastname" name="lastname">
            <input class="button" type="submit" value="Search">
        </form>

        <div id="search-results"></div>
    </div>
    <!-- Form for updating ticket information -->
    <div class="ticketButtons">
        <div>
            <?php if (!$readonly) : ?>
                <button id="unread-ticket-button" class="button">Unread Ticket</button>
            <?php endif; ?>
            <?php if ($readonly && !session_is_intern() && $ticket['status'] != 'closed' && !$hasNotes) : ?>
                <button id="close-ticket-button" class="button">Close Ticket</button>
            <?php endif; ?>
            <?php if (!$readonly || $ticket['status'] != 'closed') : ?>
                <button class="new-note-button button">New Note</button>
            <?php endif; ?>
            <?php
            if (!$readonly) {
                if ($is_ticket_flagged) {
            ?>
                    <form id="flag-form" method="post">
                        <input type="submit" class="button right" name="unflag_ticket" value="Unflag ticket" class="right">
                    </form>
                <?php } else { ?>
                    <form id="flag-form" method="post">
                        <input type="submit" class="button right" name="flag_ticket" value="Flag ticket" class="right">
                    </form>
            <?php }
            }
            ?>
        </div>
    </div>
    <form id="updateTicketForm" method="POST" action="update_ticket.php">
        <!-- Add a submit button to update the information -->
        <div class="horizontalContainer">
            <?php if (!$readonly) : ?>
                <div class="horizontalContainerCell">
                    <input id="green-button" type="submit" name="update_ticket" class="button" value="Update Ticket">
                </div>
            <?php endif; ?>
            <div class="horizontalContainerCell">
                <?php if (!$readonly || $ticket['status'] != 'closed') : ?>
                    <input id="green-button" type="submit" name="update_ticket_with_email" class="button" value="Update Ticket & Email">
                <?php endif; ?>

                <?php if ($readonly) : ?>
                    <input type="hidden" name="send_client_email" value="send_client_email" checked>
                    <input type="hidden" name="send_tech_email" value="send_tech_email" checked>
                    <?php
                    if (isset($ticket['cc_emails']) && $ticket['cc_emails'] !== '') {
                        echo '<input type="hidden" name="send_cc_emails" value="send_cc_emails" checked>';
                    }
                    if (isset($ticket['bcc_emails']) && $ticket['bcc_emails'] !== '') {
                        echo '<input type="hidden" name="send_bcc_emails" value="send_bcc_emails" checked>';
                    }
                    ?>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!$readonly) : ?>
            <div class="horizontalContainer">
                <div class="horizontalContainerCell">
                    Client:<input type="checkbox" name="send_client_email" value="send_client_email" <?= $ticket["send_client_email"] != 0 ? "checked" : "" ?>>
                </div>
                <div class="horizontalContainerCell">
                    Tech:<input type="checkbox" name="send_tech_email" value="send_tech_email" <?= $ticket["send_tech_email"] != 0 ? "checked" : "" ?>>
                </div>
                <div class="horizontalContainerCell">
                    CC:<input type="checkbox" name="send_cc_emails" value="send_cc_emails" <?= $ticket["send_cc_emails"] != 0 ? "checked" : "" ?>>
                </div>
                <div class="horizontalContainerCell">
                    BCC:<input type="checkbox" name="send_bcc_emails" value="send_bcc_emails" <?= $ticket["send_bcc_emails"] != 0 ? "checked" : "" ?>>
                </div>
            </div>
        <?php endif; ?>
        <div class="ticketGrid">
            <input type="hidden" name="ticket_create_date" value="<?= $ticket['created'] ?>">
            <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
            <input type="hidden" name="madeby" value="<?= $_SESSION['username'] ?>">
            <input type="hidden" id="client" name="client" value="<?= $ticket['client'] ?>">
            <div class="currentClient">
                <div>
                    <div class="fake-h3">Client Info </div>
                    <div id="client-display">
                        <?= $clientFirstName . " " . $clientLastName . " — " . email_address_from_username(strtolower($ticket['client'])) ?><br><br>
                        <?php
                        $result = get_ldap_info($ticket['client'], LDAP_EMPLOYEE_ID | LDAP_EMPLOYEE_LOCATION | LDAP_EMPLOYEE_JOB_TITLE);

                        $employee_id = $result["employeeid"];
                        $employee_location = $result["location"];
                        $job_title = $result["job_title"];
                        ?>
                        <?php if (!$readonly) : ?>
                            ID: <?= $employee_id ?><br>
                        <?php endif; ?>
                        Location: <?= location_name_from_id($employee_location) ?><br>
                        Job Title: <?= $job_title ?>
                    </div>
                </div>
                <?php if (!$readonly) : ?>
                    <div class="right">
                        <a id="search-client-button">Change Client</a>
                    </div>
                <?php endif; ?>
            </div>
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
                // Display Fields that client can edit
            ?>

                <div>
                    <span>Assigned Tech:</span>
                    <?php
                    if (!empty($ticket['employee'])) {
                        $tech_nice_name = get_local_name_for_user($ticket['employee']);
                        echo $tech_nice_name['firstname'] . ' ' . $tech_nice_name['lastname'];
                    } else {
                        echo "Unassigned";
                    }
                    ?>
                </div>
                <input type="hidden" id="employee" name="employee" value="<?= $ticket['employee'] ?>">

                <div>
                    <span>Location:</span> <?= location_name_from_id($ticket['location']) ?>
                </div>
                <input type="hidden" id="location" name="location" value="<?= $ticket['location'] ?>">

                <div>
                    <span>Department:</span><?php
                                            if (!empty($ticket['department'])) {
                                                echo location_name_from_id($ticket['department']);
                                            } else {
                                                echo "Not Assigned";
                                            }
                                            ?>
                </div>
                <input type="hidden" id="department" name="department" value="<?= $ticket['department'] ?>">
                <div>
                    <span>Current Status:</span> <?= $ticket['status'] ?>
                </div>
                <input type="hidden" id="status" name="status" value="<?= $ticket['status'] ?>">
                <div>
                    <span>Request Type:</span> <?= request_name_for_type($ticket['request_type_id']) ?>
                </div>
                <input type="hidden" id="request_type" name="request_type" value="<?= $ticket['location'] ?>">



                <div>
                    <span>Priority:</span> <?= getPriorityName($ticket['priority']) ?>
                </div>
                <input type="hidden" id="priority" name="priority" value="<?= $ticket['priority'] ?>">

                <input type="hidden" id="parent_ticket" name="parent_ticket" value="<?= $ticket['parent_ticket'] == 0 ? '' : $ticket['parent_ticket'] ?>">
            <?php

            } else {
                // Display Fields that tech can edit
            ?>
                <div>
                    <label for="employee">Assigned Tech:</label>
                    <select id="employee" name="employee">
                        <option value="unassigned">Unassigned</option>
                        <?php foreach ($tech_usernames as $tech_username) : ?>
                            <?php
                            $name = get_local_name_for_user($tech_username);
                            $firstname = ucwords(strtolower($name["firstname"]));
                            $lastname = ucwords(strtolower($name["lastname"]));
                            $display_string = $firstname . " " . $lastname . " - " . location_name_from_id(get_fast_client_location($tech_username) ?: "");
                            ?>
                            <option value="<?= $tech_username ?>" <?= $ticket['employee'] === $tech_username ? 'selected' : '' ?>><?= $display_string ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="department">Department:</label>
                    <select id="department" name="department">
                        <option hidden disabled selected value></option>
                        <?php
                        // Query the locations table to get the departments
                        $department_query = "SELECT sitenumber, location_name FROM locations WHERE is_department = TRUE AND is_archived = FALSE ORDER BY location_name ASC";
                        $department_result = HelpDB::get()->execute_query($department_query);

                        //Create a "Department" optgroup and create an option for each department
                        // echo '<optgroup label="Department">';
                        while ($locations = mysqli_fetch_assoc($department_result)) {
                            $selected = '';
                            if ($locations['sitenumber'] == $ticket['department']) {
                                $selected = 'selected';
                            }
                            echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
                        }
                        // echo '</optgroup>';
                        ?>
                    </select>
                </div>
                <div>
                    <label for="location">Location:</label>
                    <select id="location" name="location" required>
                        <option hidden disabled selected value></option>
                        <?php


                        // Query the locations table to get the locations
                        $location_query = "SELECT sitenumber, location_name FROM locations WHERE is_department = FALSE AND is_archived = FALSE ORDER BY location_name ASC";
                        $location_result = HelpDB::get()->execute_query($location_query);

                        // Create a "Location" optgroup and create an option for each location
                        // echo '<optgroup label="Location">';
                        while ($locations = mysqli_fetch_assoc($location_result)) {
                            $selected = '';
                            if ($locations['sitenumber'] == $ticket['location']) {
                                $selected = 'selected';
                            }
                            echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
                        }
                        // echo '</optgroup>';
                        ?>
                    </select>
                </div>
                <div>
                    <label for="status">Current Status:</label>
                    <select id="status" name="status">
                        <option value="open" <?= ($ticket['status'] == 'open') ? ' selected' : '' ?>>Open</option>
                        <option value="closed" <?= ($ticket['status'] == 'closed') ? ' selected' : '' ?>>Closed</option>
                        <option value="resolved" <?= ($ticket['status'] == 'resolved') ? ' selected' : '' ?>>Resolved</option>
                        <!-- <option value="pending" <?= ($ticket['status'] == 'pending') ? ' selected' : '' ?>>Pending</option> -->
                        <option value="vendor" <?= ($ticket['status'] == 'vendor') ? ' selected' : '' ?>>Vendor</option>
                        <option value="maintenance" <?= ($ticket['status'] == 'maintenance') ? ' selected' : '' ?>>Maintenance</option>
                    </select>
                </div>
                <div>
                    <label for="request_type">Request Type:</label>
                    <select id="request_type" name="request_type">
                        <option value="0">Select a more specific request type otherwise (Other)</option>
                        <?php
                        // Fetch the top-level request types
                        $topLevelQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_parent IS NULL ORDER BY request_name";
                        $topLevelResult = HelpDB::get()->query($topLevelQuery);

                        // Add the top-level request types as options
                        while ($topLevelRow = $topLevelResult->fetch_assoc()) {
                            $selected = '';
                            if ($topLevelRow['request_id'] == $ticket['request_type_id']) {
                                $selected = 'selected';
                            } else {
                                // Check if the ticket's request type is a child or grandchild of this top-level request type
                                $childQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_id = " . $ticket['request_type_id'] . " AND request_parent = " . $topLevelRow['request_id'];
                                $childResult = HelpDB::get()->query($childQuery);
                                if ($childResult->num_rows > 0) {
                                    $selected = 'selected';
                                } else {
                                    $grandchildQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_id = " . $ticket['request_type_id'];
                                    $grandchildResult = HelpDB::get()->query($grandchildQuery);
                                    while ($grandchildRow = $grandchildResult->fetch_assoc()) {
                                        $childQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_id = " . $grandchildRow['request_parent'] . " AND request_parent = " . $topLevelRow['request_id'];
                                        $childResult = HelpDB::get()->query($childQuery);
                                        if ($childResult->num_rows > 0) {
                                            $selected = 'selected';
                                        }
                                    }
                                }
                            }
                            echo '<option disabled value="' . $topLevelRow['request_id'] . '" ' . $selected . '>' . $topLevelRow['request_name'] . '</option>';

                            // Fetch the child request types
                            $childQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_parent = " . $topLevelRow['request_id'] . " ORDER BY request_name";
                            $childResult = HelpDB::get()->query($childQuery);

                            // Add the child request types as options
                            while ($childRow = $childResult->fetch_assoc()) {
                                $selected = '';
                                if ($childRow['request_id'] == $ticket['request_type_id']) {
                                    $selected = 'selected';
                                } else {
                                    // Check if the ticket's request type is a grandchild of this child request type
                                    $grandchildQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_id = " . $ticket['request_type_id'] . " AND request_parent = " . $childRow['request_id'];
                                    $grandchildResult = HelpDB::get()->query($grandchildQuery);
                                    if ($grandchildResult->num_rows > 0) {
                                        $selected = 'selected';
                                    }
                                }
                                echo '<option value="' . $childRow['request_id'] . '" ' . $selected . '>&nbsp;&nbsp;&nbsp;&nbsp;' . $childRow['request_name'] . '</option>';

                                // Fetch the grandchild request types
                                $grandchildQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_parent = " . $childRow['request_id'] . " ORDER BY request_name";
                                $grandchildResult = HelpDB::get()->query($grandchildQuery);

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
                    <label for="priority">Priority:</label>
                    <select id="priority" name="priority">
                        <option value="1" <?= ($ticket['priority'] == '1') ? ' selected' : '' ?>>Critical</option>
                        <option value="3" <?= ($ticket['priority'] == '3') ? ' selected' : '' ?>>Urgent</option>
                        <option value="5" <?= ($ticket['priority'] == '5') ? ' selected' : '' ?>>High</option>
                        <option value="10" <?= ($ticket['priority'] == '10') ? ' selected' : '' ?>>Standard</option>
                        <option value="15" <?= ($ticket['priority'] == '15') ? ' selected' : '' ?>>Client Response</option>
                        <?php
                        if (
                            $_SESSION['permissions']['is_supervisor'] != 0 ||
                            $_SESSION['permissions']['is_admin'] != 0 ||
                            $ticket['client'] == $ticket['employee'] ||
                            $ticket['priority'] == '30'
                        ) :
                        ?>
                            <option value="30" <?= ($ticket['priority'] == '30') ? ' selected' : '' ?>>Project</option>
                        <?php endif; ?>
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
        <label for="intern_ticket_status">Intern Ticket:</label>
        <input type="checkbox" id="intern_ticket_status" name="intern_ticket_status" <?php if ($ticket['intern_visible']) echo "checked"; ?> <?php if ($readonly) echo "onclick='return false;'"; ?>>


        <div class="detailContainer">
            <div class="ticketSubject">
                <label for="ticket_name">Ticket Title:</label>
                <input type="text" id="ticket_name" name="ticket_name" value="<?= $ticket['name'] ?>" maxlength="100">
            </div>
            <label for="description" class="heading2">Request Detail:</label>
            <div class="ticket-description">
                <?php
                $ticket_pattern = "/WO#\\d{1,6}/";
                $archived_ticket_pattern = "/WO#A-\\d{1,6}/";
                $asset_tag_pattern = "/(BC|SN|bc|sn)#([\w]*)(\s|$|)/";
                if ($ticket['description'] !== null) {
                    $request_detail = sanitize_html($ticket['description']);
                }

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
                        $scheme = null;

                        if (str_starts_with(strtolower($match_str), 'bc')) {
                            $scheme = 'barcode';
                        } else {
                            $scheme = 'sn';
                        }

                        // BC# and SN# have the same characters so the first 3 can be trimmed in both
                        $value = substr($match_str, 3);

                        // when doing https:// the : kept disappearing, not sure why
                        // will just let it choose https automatically
                        $url = "<a target=\"_blank\" href=\"//vault.provo.edu/nac_edit.php?$scheme=$value\">$match_str</a>";

                        $request_detail = str_replace($match_str, $url, $request_detail);
                    }
                }
                echo html_entity_decode($request_detail);

                ?>
                <?php
                if ($_SESSION['permissions']['is_admin'] == 1) {
                ?>
                    <button id="edit-description-button" class="button" type="button">Edit Request Detail</button>
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
        <ul id="file_list">
            <?php
            foreach ($attachmentPaths as $attachmentPath) {
                $path = basename($attachmentPath);
                $path_encoded = urlencode($path);
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $shouldUseLightbox = $extension == "jpeg" || $extension == "jpg" || $extension == "png" || $extension == "webp" || $extension == "heic";

                if (session_is_tech()) {
                    if ($shouldUseLightbox && $data = get_attachment_data($path)) {
                        echo "<li>
                                <a href=\"$data\" data-lightbox=\"image-1\" data-gallery=\"multiimages\" data-toggle=\"lightbox\">$path</a>
                                <a class='file_download' href=\"$data\" download=\"$path\" class='file_download'>&darr;</a>
                                <a class='file_del' onclick=\"confirmDeleteAttachment('$attachmentPath')\">&times;</a>
                              </li>";
                    } else {
                        echo "<li>
                                <a href=\"/upload_viewer.php?file=$path_encoded\">$path</a>
                                <a class='file_download' href=\"/upload_viewer.php?file=$path_encoded\" download=\"$path\" class='file_download'>&darr;</a>
                                <a class='file_del' onclick=\"confirmDeleteAttachment('$attachmentPath')\">&times;</a>
                              </li>";
                    }
                } else {
                    if ($shouldUseLightbox && $data = get_attachment_data($path)) {
                        echo "<li>
                                <a href=\"$data\" data-lightbox=\"image-1\" data-gallery=\"multiimages\" data-toggle=\"lightbox\">$path</a>
                                <a class='file_download' href=\"$data\" download=\"$path\" class='file_download'>&darr;</a>
                              </li>";
                    } else {
                        echo "<li>
                                <a href=\"/upload_viewer.php?file=$path_encoded\">$path</a>
                                <a class='file_download' href=\"/upload_viewer.php?file=$path_encoded\" download=\"$path\" class='file_download'>&darr;</a>
                              </li>";
                    }
                }
            }
            ?>
        </ul>

    <?php
        $hasfiles = true;
    }
    ?>



    <div id="file-upload-form" style="display: none;">
        <h3>Upload Files</h3>
        <p class="help-message">When you click 'Choose Files', a dialog box will appear. You can select either one file or multiple files at once from your computer. After making your selection, remember to click 'Upload' to attach the files to the ticket.</p>
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
    <?php if (!$readonly || $ticket['status'] != 'closed') : ?>
        <button id="toggle-file-upload-form" class="button">Attach <?= isset($hasfiles) && $hasfiles ? 'Additional' : '' ?> Files</button>
    <?php endif; ?>

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
                    <th>Ticket Location</th>
                </tr>
                <?php
                foreach ($child_tickets as $child_ticket) {
                ?>
                    <tr>
                        <td data-cell="Ticket ID"><a href="edit_ticket.php?id=<?= $child_ticket['id'] ?>"><?= $child_ticket['id'] ?></a></td>
                        <td data-cell="Status"><?= $child_ticket['status'] ?></td>
                        <td data-cell="Tech"><?= $child_ticket['employee'] ?></td>
                        <td data-cell="Ticket Title"><?= $child_ticket['name'] ?></td>
                        <td data-cell="Request Detail" class="child-ticket-details"><?= mb_substr(strip_tags(html_entity_decode($child_ticket['description'])), 0, 100) ?>...</td>
                        <td data-cell="Ticket Location"><?= location_name_from_id($child_ticket['location']) ?></td>
                    </tr>
                <?php
                }
                ?>
            </table>

        </div>
    <?php
    }
    ?>
    <h2>Tasks</h2>
    <?php
    // Show existing tasks on ticket
    $tasks_res = HelpDB::get()->execute_query("SELECT id, description, completed, required, assigned_tech FROM help.ticket_tasks WHERE ticket_id = ?", [$ticket_id]);
    $task_rows = $tasks_res->fetch_all(MYSQLI_ASSOC);

    if (count($task_rows) > 0) {
    ?>
        <table class="taskTable">
            <tr>
                <th>Assigned Tech</th>
                <th>Task Description</th>
                <th>Completed</th>
                <th>Edit Task</th>
            </tr>
            <?php
            foreach ($task_rows as $row) {
                $task_complete = isset($row['completed']) && $row['completed'] != 0;
                $task_required = isset($row['required']) && $row['required'] != 0;
                $task_id = $row['id'];
                $assigned_tech = $row['assigned_tech'];
                $checked_if_done = $task_complete ? "checked" : "";
                $checked_if_required = $task_required ? "checked" : "";

                if (isset($assigned_tech) && $assigned_tech != "unassigned") {
                    $assigned_tech_name = get_local_name_for_user($assigned_tech);
                    $assigned_tech_str = $assigned_tech_name["firstname"] . " " . $assigned_tech_name["lastname"];
                } else {
                    $assigned_tech_str = "Unassigned";
                }
            ?>
                <tr>
                    <td data-cell="Assigned Tech"><?= $assigned_tech_str ?></td>
                    <td data-cell="Task Description"><?= htmlspecialchars($row['description']); ?></td>
                    <td data-cell="Completed"><input type="checkbox" onclick="taskStatusChanged(this, '<?= $task_id ?>');" <?= $checked_if_done ?> /></td>
                    <td data-cell="Edit Task"><button class="button" onclick="location.href='/controllers/tasks/edit_task.php?task_id=<?= $task_id ?>'">Edit Task</button></td>
                </tr>
            <?php
            }
            ?>
        </table><br>
    <?php
    }
    ?>

    <?php if (!$readonly || $ticket['status'] != 'closed') : ?>
        <button id="new-task-button" class="button">Add Task</button><br>
    <?php endif; ?>

    <div id="new-task-form-background" class="modal-form-background">
        <div id="new-task-form" class="modal-form" style="display: none;">
            <div class="modal-form-header"><span id="new-task-form-close">&times;</span></div>
            <h3>Add Task</h3>
            <form id="task-submit" method="post" action="add_task_handler.php">
                <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
                <input type="hidden" name="username" value="<?= $_SESSION['username'] ?>">
                <div>
                    <div>
                        <label for="assigned_tech">Assigned Tech: </label>
                        <select id="assigned-tech" name="assigned_tech">
                            <option value="">Unassigned</option>
                            <?php foreach ($tech_usernames as $username) : ?>
                                <?php
                                $name = get_local_name_for_user($username);
                                $firstname = $name["firstname"];
                                $lastname = $name["lastname"];
                                $display_string = $firstname . " " . $lastname . " - " . location_name_from_id(get_fast_client_location($username) ?: "");
                                ?>
                                <option value="<?= $username ?>"><?= $display_string ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="task_description">Task description: </label>
                        <textarea id="task-description" name="task_description" class="tinyMCEtextarea"></textarea>
                    </div>
                    <div>
                        <label for="task_complete">Completed: </label>
                        <input type="checkbox" name="task_complete"></input>
                    </div>
                    <div>
                        <label for="required">Required: </label>
                        <input type="checkbox" name="required" checked></input>
                    </div>
                </div>
                <input style="margin-top: 20px;" type="submit" class="button" value="Submit Task">
            </form>
        </div>
    </div>
    <!-- Loop through the notes and display them -->


    <h2>Notes</h2>
    <?php if (!$readonly || $ticket['status'] != 'closed' && $hasNotes) : ?>
        <button class="new-note-button button">New Note</button>
    <?php endif; ?>
    <div id="note-table" class="note">
        <table class="ticketsTable">
            <tr>
                <th class='tableDate'>Date</th>
                <th class="tableUser">Created By</th>
                <th class="tableString">Note</th>
                <th class="timeColumn">Time</th>
            </tr>
            <?php
            $total_minutes = 0;
            $total_hours = 0;
            $num_notes = 0;
            $hidden_note_count = $total_note_count - $note_count;
            $note_str = $hidden_note_count == 1 ? "note" : "notes";
            if ($total_note_count > $note_count):
            ?>
                <tr id="expand-row">
                    <td colspan=4><a onclick="toggleRowVisibility(<?= $hidden_note_count ?>);" id="expand-row-button">Expand <?= $hidden_note_count ?> more <?= $note_str ?>...</a></td>
                </tr>
            <?php
            endif;
            foreach ($notes as $note) :
                // Hidden notes should only be viewable by admins
                if (
                    $note['visible_to_client'] == 0 &&
                    !$_SESSION['permissions']['is_tech']
                )
                    continue;
                $num_notes++;

                // Add the total time for this note to the total time for all notes
                $total_minutes += $note['work_minutes'] + $note['travel_minutes'];
                $total_hours += $note['work_hours'] + $note['travel_hours'];

                // if $hidden_note_count < 0, always show
                if ($num_notes <= $hidden_note_count)
                    echo "<tr class='hidden-note-row' style='display:none;'>";
                else
                    echo "<tr>";
            ?>
                <td data-cell="Date"><a href="edit_note.php?note_id=<?= $note['note_id'] ?>&ticket_id=<?= $ticket_id ?>">
                        <?php
                        $date_override = $note['date_override'];
                        if ($date_override != null)
                            echo $date_override . "*";
                        else
                            echo $note['created'];
                        ?></a></td>
                <td data-cell="Created By"><?php
                                            $creator = $note['creator'];

                                            // Check if creator exists as a user
                                            $creator_found_result = HelpDB::get()->execute_query('SELECT COUNT(*) AS count FROM users WHERE username = ?', [$creator]);
                                            $creator_found_data = $creator_found_result->fetch_assoc();
                                            if (isset($creator) && $creator_found_data["count"] > 0) {
                                                $name = get_local_name_for_user($creator);
                                                echo $name['firstname'] . ' ' . $name['lastname'];
                                            } else {
                                                echo $creator;
                                            }
                                            ?></td>
                <td class="ticket_note" data-cell="Note Message">
                    <?php
                    $ticket_pattern = "/WO#\\d{1,6}/";
                    $archived_ticket_pattern = "/WO#A-\\d{1,6}/";
                    $asset_tag_pattern = "/(BC|SN|bc|sn)#([\w]*)(\s|$|)/";
                    if ($note['note'] !== null) {
                        $note_data = strip_tags(sanitize_html($note['note']));
                    }
                    if (isset($note_data)) {

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
                                $scheme = null;

                                if (str_starts_with(strtolower($match_str), 'bc')) {
                                    $scheme = 'barcode';
                                } else {
                                    $scheme = 'sn';
                                }

                                // BC# and SN# have the same characters so the first 3 can be trimmed in both
                                $value = substr($match_str, 3);

                                // when doing https:// the : kept disappearing, not sure why
                                // will just let it choose https automatically
                                $url = "<a target=\"_blank\" href=\"//vault.provo.edu/nac_edit.php?$scheme=$value\">$match_str</a>";

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

            <tr class="totalTime">
                <td data-cell="Total Time" colspan=4>
                    <?php
                    displayTotalTime($total_hours, $total_minutes);
                    ?>
                </td>
            </tr>
        </table>
    </div>
    <?php if (!$readonly || $ticket['status'] != 'closed') : ?>
        <button class="new-note-button button">New Note</button>
    <?php endif; ?>
    <div>
        <div id="new-note-form" style="display: none;">
            <h3>New Note</h3>
            <form id="note-submit" method="post" action="add_note_handler.php">
                <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
                <div>
                    <label for="note">Note:</label>
                    <textarea id="note" name="note" class="tinyMCEtextarea"></textarea>
                </div>
                <?php
                if (session_is_tech()) {
                ?>
                    <div class="flex">
                        <div>
                            <label for="visible_to_client">Visible to Client:</label>
                            <input type="checkbox" id="visible_to_client" name="visible_to_client" checked="checked">
                        </div>

                        <div><a href="/note_shortcuts.php">Note Shorthand</a></div>
                    </div>

                    <?php
                    $exclude_result = HelpDB::get()->execute_query("SELECT created FROM notes WHERE creator = ? ORDER BY created DESC LIMIT 1", [$_SESSION['username']]);
                    $row = $exclude_result->fetch_assoc();
                    $last_note_time = $row['created'];
                    if ($last_note_time != null) {
                        $date = new DateTime($last_note_time);
                        $formatted_time = $date->format('Y-m-d h:i A');
                        echo "<p>Last note created by " . $_SESSION['username'] . " at: " . $formatted_time . "</p>";
                    } else {
                        echo "<p>No notes found for this user</p>";
                    }
                    ?>
                    <h4>Work Time</h4>
                    <div class="time_input">
                        <label for="work_hours">Hours:</label>
                        <input id="work_hours" name="work_hours" type="number" value="0" required>

                        <label for="work_minutes">Minutes:</label>
                        <input id="work_minutes" name="work_minutes" type="number" value="0" required>
                    </div>
                    <h4>Travel Time</h4>
                    <div class="time_input">
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
                <input type="submit" class="button" value="Submit Note">
            </form>
            <script src="/includes/js/external/jquery-3.7.1.min.js"></script>
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
    $log_query = "SELECT * FROM ticket_logs WHERE ticket_id = ? ORDER BY created_at DESC";
    $log_stmt = mysqli_prepare(HelpDB::get(), $log_query);
    mysqli_stmt_bind_param($log_stmt, "i", $ticket_id);
    mysqli_stmt_execute($log_stmt);
    $log_result = mysqli_stmt_get_result($log_stmt);

    // Display the ticket logs in a table
    if (session_is_tech() && mysqli_num_rows($log_result) > 0) {
    ?>
        <div class="ticket_log">
            <h2 id="ticket-history-status">Expand Ticket History <span id="chevron" style="font-size: 0.8em;">&#x2192;</span></h2>
            <table id="ticket-history">
                <tr class="ticket-history-header">
                    <th class="tableDate">Created At</th>
                    <th class="tableUser">Changed By</th>
                    <th class="tableString">Changes made</th>
                </tr>
                <?php
                while ($log_row = mysqli_fetch_assoc($log_result)) {
                    $uniqueNoteId = $log_row['id'];
                ?>
                    <tr>
                        <td data-cell="Date"><?= $log_row['created_at'] ?></td>
                        <td data-cell="Created by"><?= $log_row['user_id'] ?></td>
                        <td class="ticket_note" data-cell="Change Made">
                            <?php
                            $note_str = "";
                            $old_value = sanitize_html(html_entity_decode(test_input($log_row['old_value'])));
                            $new_value = sanitize_html(html_entity_decode(test_input($log_row['new_value'])));
                            switch ($log_row['field_name']) {
                                case 'Attachment':
                                    $note_str = generateUpdateHTML('Attachment', null, $new_value, 'Added', $uniqueNoteId);
                                    break;
                                case 'notedeleted':
                                    $note_str = generateUpdateHTML('Note', $old_value, null, 'Deleted', $uniqueNoteId);
                                    break;
                                case 'note':
                                    $note_str = generateUpdateHTML('Note', $old_value, $new_value, $old_value != null ? 'Updated' : 'Created', $uniqueNoteId);
                                    break;
                                case 'description':
                                    $note_str = generateUpdateHTML('Description', $old_value, $new_value, $old_value != null ? 'Updated' : 'Created', $uniqueNoteId);
                                    break;
                                case 'sent_emails':
                                    $note_str = $new_value;
                                    break;
                                case 'created':
                                    $note_str = 'Ticket Created';
                                    break;
                                case str_contains($log_row['field_name'], 'Task'):
                                    $note_str = $log_row['field_name'];
                                    break;
                                case 'location':
                                    $note_str = 'Location Changed From: ' . location_name_from_id($old_value) . ' To: ' . location_name_from_id($new_value);
                                    break;
                                case 'department':
                                    $note_str = 'Location Changed From: ' . location_name_from_id($old_value) . ' To: ' . location_name_from_id($new_value);
                                    break;
                                case 'priority':
                                    $note_str = 'Priority Changed From: ' . getPriorityName($old_value) . ' To: ' . getPriorityName($new_value);
                                    break;
                                case 'request_type_id':
                                    $note_str = 'Request Type Changed From: ' . request_name_for_type($old_value) . ' To: ' . request_name_for_type($new_value);
                                    break;
                                default:
                                    $note_str = formatFieldName($log_row['field_name']) . ' From: ' . html_entity_decode($old_value) . ' To: ' . html_entity_decode($new_value);
                                    break;
                            }
                            echo $note_str;
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
    <?php if ($_SESSION['permissions']['is_tech']) : ?>
        <br>
        <form id="merge-form" method="post" action="merge_tickets_handler.php">
            <label for="merge_ticket_id">Merge this ticket into:</label>
            <input type="hidden" name="ticket_id_source" value="<?= $ticket_id ?>">
            <input type="text" name="ticket_id_host" value=""><br>
            <input type="submit" class="button" value="Merge">
        </form>
    <?php endif; ?>
</article>
<script>
    // Make links in note content open in new tab
    $('.note-content a').attr('target', '_blank');

    // Toggle ticket history visibility when clicked
    $('#ticket-history-status').click(function() {
        const ticketHistoryStatus = document.getElementById("ticket-history-status");
        const ticketHistory = $('#ticket-history');
        const chevron = document.getElementById("chevron");
        let ticketHistoryStatusText = ticketHistoryStatus.textContent.trim();

        if (ticketHistoryStatusText.includes("Expand Ticket History")) {
            ticketHistoryStatusText = "Hide Ticket History";
            chevron.innerHTML = "&#x2193;"; // Down chevron
        } else if (ticketHistoryStatusText.includes("Hide Ticket History")) {
            ticketHistoryStatusText = "Expand Ticket History";
            chevron.innerHTML = "&#x2192;"; // Right chevron
        }

        ticketHistoryStatus.textContent = ticketHistoryStatusText;
        ticketHistoryStatus.appendChild(chevron); // Re-append the chevron
        ticketHistory.toggle();
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
<script src="/includes/js/note_submit.js?v=<?= $app_version ?>" type="text/javascript"></script>
<script src="/includes/js/pages/edit_ticket.js?v=<?= $app_version ?>" type="text/javascript"></script>
<?php include("footer.php"); ?>


<script>
    // Pass the user's role from PHP to JavaScript
    var isTechUser = <?php echo $_SESSION['permissions']['is_tech'] ? 'true' : 'false'; ?>;

    // Pass the note order from PHP to JavaScript
    var noteOrder = "<?php echo $note_order; ?>";
    // Close Ticket Button call to AJAX
    $(document).ready(function() {
        $('#close-ticket-button').click(function() {
            $.ajax({
                url: "/ajax/close_ticket.php",
                method: "POST",
                data: {
                    ticket_id: <?= $ticket_id ?>,
                },
                success: function(data, textStatus, xhr) {
                    console.log("Ticket closed successfully");
                    location.reload();
                },
                error: function() {
                    alert("Error: Autocomplete AJAX call failed");
                },
            });
        });
    });
    // Unread Button Call to AJAX
    $(document).ready(function() {
        $('#unread-ticket-button').click(function() {
            $.ajax({
                url: "/ajax/unread_ticket.php",
                method: "POST",
                data: {
                    ticket_id: <?= $ticket_id ?>,
                },
                success: function(data, textStatus, xhr) {
                    console.log("Ticket unread successfully");
                    window.location.href = "/tickets.php";
                },
                error: function() {
                    alert("Error: Autocomplete AJAX call failed");
                },
            });
        });
    });

    function taskStatusChanged(obj, task_id) {
        $.ajax({
            url: "/ajax/ticket_tasks/update_task.php",
            method: "POST",
            data: {
                task_id: task_id,
                new_status: obj.checked ? 1 : 0,
                update_type: "completed_change"
            },
            success: function(data, textStatus, xhr) {
                console.log("Ticket task status changed successfully");
            },
            error: function() {
                alert("Error: Ticket task status AJAX call failed");
            },
        });
    }

    function confirmDeleteTask(task_id) {
        if (confirm("Are you sure you want to delete this task?")) {
            deleteTask(task_id);
        }
    }

    function deleteTask(task_id) {
        $.ajax({
            url: "/ajax/ticket_tasks/delete_task.php",
            method: "POST",
            data: {
                task_id: task_id,
            },
            success: function(data, textStatus, xhr) {
                alert("Ticket task deleted successfully");
            },
            error: function() {
                alert("Error: Ticket task deletion AJAX call failed");
            },
        });
    }

    function editTask(task_id) {
        console.log(task_id);
    }

    function confirmDeleteAttachment(attachmentPath) {
        const basename = attachmentPath.split(/[\\/]/).pop();
        const result = confirm("Are you sure you want to delete attachment \'" + basename + "\'?");
        if (result) {
            deleteAttachment(attachmentPath);
        }
    }

    function deleteAttachment(attachmentPath) {
        const ticket_id = <?= $ticket_id ?>;

        $.ajax({
            url: "/ajax/delete_attachment.php",
            method: "POST",
            data: {
                attachment_path: attachmentPath,
                ticket_id: ticket_id
            },
            success: function(data, textStatus, xhr) {
                // alert("Attachment deleted successfully");
                location.reload();
            },
            error: function() {
                alert("Error: Attachment deletion AJAX call failed");
            },
        });
    }

    function toggleRowVisibility(num_items) {
        // show previously hidden rows
        const rows = document.getElementsByClassName("hidden-note-row");
        for (const row of rows) {
            if (row.style.display == "none")
                row.style.display = "revert";
            else
                row.style.display = "none";
        }

        const num_items_str = num_items.toString();
        let note_str = num_items == 1 ? "note" : "notes";

        const expand_row = document.getElementById("expand-row-button");
        if (expand_row.textContent.includes("Expand"))
            expand_row.textContent = `Collapse ${num_items_str} ${note_str}...`;
        else
            expand_row.textContent = `Expand ${num_items_str} more ${note_str}...`;
    }
</script>