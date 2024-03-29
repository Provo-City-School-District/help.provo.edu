<?php
require_once("block_file.php");
include("header.php");
require_once('helpdbconnect.php');
require_once('swdbconnect.php');
include("ticket_utils.php");
// Query the locations table to get the location information
$location_query = "SELECT sitenumber, location_name FROM locations ORDER BY location_name ASC";
$location_result = $database->execute_query($location_query);
$search_id = '';
$search_name = '';
$search_location = '';
$search_employee = '';
$search_client = '';
$search_status = '';
$search_priority = '';
$search_start_date = '';
$search_end_date = '';
$dates_searched = [];

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    if (!empty($_GET)) {

        // Get the search terms from the form
        $search_id = isset($_GET['search_id']) ? mysqli_real_escape_string($database, $_GET['search_id']) : '';
        $search_name = isset($_GET['search_name']) ? mysqli_real_escape_string($database, $_GET['search_name']) : '';
        $search_location = isset($_GET['search_location']) ? mysqli_real_escape_string($database, $_GET['search_location']) : '';
        $search_employee = isset($_GET['search_employee']) ? mysqli_real_escape_string($database, $_GET['search_employee']) : '';
        $search_client = isset($_GET['search_client']) ? mysqli_real_escape_string($database, $_GET['search_client']) : '';
        $search_status = isset($_GET['search_status']) ? mysqli_real_escape_string($database, $_GET['search_status']) : '';
        $search_priority = isset($_GET['priority']) ? mysqli_real_escape_string($database, $_GET['priority']) : '';
        $search_start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($database, $_GET['start_date']) : '';
        $search_end_date = isset($_GET['end_date']) ? mysqli_real_escape_string($database, $_GET['end_date']) : '';
        $dates_searched = isset($_GET['dates']) ? $_GET['dates'] : [];
        $search_archived = isset($_GET['search_archived']) ? mysqli_real_escape_string($database, $_GET['search_archived']) : '';


        // Query the archived_location_id values for the given sitenumber
        $archived_location_ids = array();
        $arch_location_query = "SELECT archived_location_id FROM locations WHERE sitenumber = '$search_location'";
        $arch_location_result = $database->query($arch_location_query);
        if ($arch_location_result->num_rows > 0) {
            while ($arch_row = $arch_location_result->fetch_assoc()) {
                $archived_location_ids[] = $arch_row['archived_location_id'];
            }
        }
        require_once('search_query_builder.php');
        // Execute the SQL query to search for matching tickets
        $ticket_result = mysqli_query($database, $ticket_query);
        if ($search_archived == 1) {
            //include archived tickets in search
            require_once('search_archived_query_builder.php');
            $old_ticket_result = mysqli_query($swdb, $old_ticket_query);
        }

        // Combine the results from both queries into a single array
        $combined_results = array();
        while ($row = mysqli_fetch_assoc($ticket_result)) {
            $combined_results[] = $row;
        }
        //add in archived tickets if the checkbox is checked
        if ($search_archived == 1) {
            while ($row = mysqli_fetch_assoc($old_ticket_result)) {
                $combined_results[] = $row;
            }
        }
    }
}

// Fetch the list of usernames from the users table
$usernamesQuery = "SELECT username,is_tech FROM users ORDER BY username ASC";
$usernamesResult = $database->execute_query($usernamesQuery);

if (!$usernamesResult) {
    die('Error fetching usernames: ' . mysqli_error($database));
}

// Store the usernames in an array
$usernames = array();
$techusernames = array();
while ($usernameRow = mysqli_fetch_assoc($usernamesResult)) {

    if ($usernameRow['is_tech'] == 1) {
        $techusernames[] = strtolower($usernameRow['username']);
    } else {
        $usernames[] = strtolower($usernameRow['username']);
    }
}

asort($techusernames);

// TODO could cache these
function get_client_name_from_id(string $client_sw_id)
{
    global $swdb;

    $client_name_result = $swdb->execute_query("SELECT FIRST_NAME, LAST_NAME FROM client WHERE CLIENT_ID = ?", [$client_sw_id]);
    $client_name_data = mysqli_fetch_assoc($client_name_result);
    $client_name = trim($client_name_data["FIRST_NAME"]) . " " . trim($client_name_data["LAST_NAME"]);

    return $client_name;
}

// TODO could cache these
function get_tech_name_from_id(string $tech_sw_id)
{
    global $swdb;

    $tech_name_result = $swdb->execute_query("SELECT FIRST_NAME, LAST_NAME FROM tech WHERE CLIENT_ID = ?", [$tech_sw_id]);
    $tech_name_data = mysqli_fetch_assoc($tech_name_result);
    $tech_name = trim($tech_name_data["FIRST_NAME"]) . " " . trim($tech_name_data["LAST_NAME"]);

    return $tech_name;
}

// TODO could cache these
function get_location_name_from_id(int $location_sw_id, $archived)
{
    global $database;
    $location_name = "";

    if ($archived) {
        $location_name_result = $database->execute_query("SELECT location_name FROM locations WHERE archived_location_id = ?", [$location_sw_id]);
    } else {
        $location_name_result = $database->execute_query("SELECT location_name FROM locations WHERE sitenumber = ?", [$location_sw_id]);
    }

    $location_name_data = mysqli_fetch_assoc($location_name_result);
    if (is_array($location_name_data) && isset($location_name_data["location_name"])) {
        $location_name = trim($location_name_data["location_name"]);
    }

    return $location_name;
}

function sortByDate($x, $y)
{
    return $x['date'] < $y['date'];
}

?>

<article id="ticketWrapper">
    <h1>Search Tickets</h1>
    <form method="get" action="search_tickets.php" id="searchForm">
        <div class="form-group">
            <label for="search_id">Ticket ID:</label>
            <input type="number" class="form-control" id="search_id" name="search_id" value="<?php echo htmlspecialchars($search_id); ?>">
        </div>
        <div class="form-group">
            <label for="search_name">Keywords:</label>
            <input type="text" class="form-control" id="search_name" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>">
        </div>
        <div class="form-group">
            <label for="priority">Priority:</label>
            <select id="priority" name="priority">
                <option value="" selected></option>

                <option value="1" <?= ($search_priority == '1') ? ' selected' : '' ?>>Critical</option>
                <option value="3" <?= ($search_priority == '3') ? ' selected' : '' ?>>Urgent</option>
                <option value="5" <?= ($search_priority == '5') ? ' selected' : '' ?>>High</option>
                <option value="10" <?= ($search_priority == '10') ? ' selected' : '' ?>>Standard</option>
                <option value="15" <?= ($search_priority == '15') ? ' selected' : '' ?>>Client Response</option>
                <option value="30" <?= ($search_priority == '30') ? ' selected' : '' ?>>Project</option>
                <option value="60" <?= ($search_priority == '60') ? ' selected' : '' ?>>Meeting Support</option>
            </select>
        </div>
        <div class="form-group">
            <label for="search_location">Department/Location:</label>
            <!-- <input type="text" class="form-control" id="search_location" name="search_location" value="<?php echo htmlspecialchars($search_location); ?>"> -->
            <select id="search_location" name="search_location">
                <option value="" selected></option>
                <?php
                // Query the locations table to get the departments
                $department_query = "SELECT * FROM locations WHERE is_department = TRUE ORDER BY location_name ASC";
                $department_result = $database->execute_query($department_query);

                // Create a "Department" optgroup and create an option for each department
                echo '<optgroup label="Department">';
                while ($locations = mysqli_fetch_assoc($department_result)) {
                    $selected = '';
                    if (isset($search_location) && $locations['sitenumber'] == $search_location) {
                        $selected = 'selected';
                    }
                    echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
                }
                echo '</optgroup>';

                // Query the locations table to get the locations
                $location_query = "SELECT * FROM locations WHERE is_department = FALSE ORDER BY location_name ASC";
                $location_result = $database->execute_query($location_query);

                // Create a "Location" optgroup and create an option for each location
                echo '<optgroup label="Location">';
                while ($locations = mysqli_fetch_assoc($location_result)) {
                    $selected = '';
                    if (isset($search_location) && $locations['sitenumber'] == $search_location) {
                        $selected = 'selected';
                    }
                    echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
                }
                echo '</optgroup>';
                ?>
            </select>
        </div>
        <?= $search_employee ?>
        <div class="form-group">
            <label for="search_employee">Tech:</label>
            <!-- <input type="text" class="form-control" id="search_employee" name="search_employee" value="<?php echo htmlspecialchars($search_employee); ?>"> -->
            <select id="search_employee" name="search_employee">
                <option value="" selected></option>
                <option value="Unassigned" <?= $search_employee == 'helpdesk' ? 'selected' : '' ?>>Unassigned</option>
                <?php foreach ($techusernames as $techuser) : ?>
                    <option value="<?= $techuser ?>" <?= $search_employee === $techuser ? 'selected' : '' ?>><?= $techuser ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="search_client">Client:</label>
            <input type="text" class="form-control" id="search_client" name="search_client" value="<?php echo htmlspecialchars($search_client); ?>">
        </div>
        <div class="form-group">
            <label for="search_status">Status:</label>
            <select id="status" name="search_status">
                <option value="" selected></option>
                <option value="open" <?= ($search_status == 'open' || $search_status == 1) ? ' selected' : '' ?>>Open</option>
                <option value="closed" <?= ($search_status == 'closed' || $search_status == 3) ? ' selected' : '' ?>>Closed</option>
                <option value="resolved" <?= ($search_status == 'resolved' || $search_status == 5) ? ' selected' : '' ?>>Resolved</option>
                <!-- <option value="pending" <?= ($search_status == 'pending' || $search_status == 7) ? ' selected' : '' ?>>Pending</option> -->
                <option value="vendor" <?= ($search_status == 'vendor' || $search_status == 12) ? ' selected' : '' ?>>Vendor</option>
                <option value="maintenance" <?= ($search_status == 'maintenance' || $search_status == 11) ? ' selected' : '' ?>>Maintenance</option>
            </select>
        </div>

        <div>

            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">

            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">

            <input type="checkbox" name="dates[]" value="created" <?php echo (isset($_GET['dates']) && in_array('created', $_GET['dates'])) ? 'checked' : ''; ?>> Created Date
            <input type="checkbox" name="dates[]" value="last_updated" <?php echo (isset($_GET['dates']) && in_array('last_updated', $_GET['dates'])) ? 'checked' : ''; ?>> Last Updated
            <input type="checkbox" name="dates[]" value="due_date" <?php echo (isset($_GET['dates']) && in_array('due_date', $_GET['dates'])) ? 'checked' : ''; ?>> Due Date
        </div>
        <div>
            <label for="search_name">Search Archived Tickets:</label>
            <input type="checkbox" id="search_archived" name="search_archived" value="1" <?php echo (isset($_GET['search_archived']) == 1) ? 'checked' : ''; ?>>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <br><br>
        <button type="reset" id="resetBtn" class="btn btn-secondary">Reset</button>
    </form>

    <?php if ($combined_results > 0) {
    ?>
        <h2>Search Results</h2>
        <table class="ticketsTable search-data-table">
            <thead>
                <tr>
                    <th class="tID">ID</th>
                    <th>Subject</th>
                    <th>Request Detail</th>
                    <th>Latest Note</th>
                    <th class="tLocation">Location</th>
                    <th>Request Category</th>
                    <th class="tUser">Assigned Tech</th>
                    <th>Current Status</th>
                    <th>Priority</th>
                    <th class="tDate">Created</th>
                    <th class="tDate">Last Updated</th>
                    <th class="tDate">Due</th>
                </tr>
            </thead>
            <tbody>
                <?php

                // Display the search results in an HTML table
                foreach ($combined_results as $row) {
                ?>
                    <tr>
                        <?php
                        $notes_query = "SELECT creator, note FROM help.notes WHERE linked_id = ? ORDER BY
                        (CASE WHEN date_override IS NULL THEN created ELSE date_override END) DESC
                    ";
                        $notes_stmt = mysqli_prepare($database, $notes_query);
                        $creator = null;
                        $note_data = null;
                        if ($notes_stmt) {
                            mysqli_stmt_bind_param($notes_stmt, "i", $row["id"]);
                            mysqli_stmt_execute($notes_stmt);

                            mysqli_stmt_bind_result($notes_stmt, $creator, $note_data);
                            // Fetch the result
                            mysqli_stmt_fetch($notes_stmt);

                            // Use $location_name as needed
                            mysqli_stmt_close($notes_stmt);
                        }

                        $latest_note_str = "";
                        if ($creator != null && $note_data != null) {
                            $latest_note_str = "<strong>$creator:</strong> " . strip_tags(html_entity_decode(html_entity_decode($note_data)));
                        }

                        if (isset($row['id'])) {
                            $descriptionWithouthtml = strip_tags(html_entity_decode($row["description"]));
                        ?>
                            <td data-cell="ID"><a href="/controllers/tickets/edit_ticket.php?id=<?= $row["id"]; ?>&nr=1"><?= $row["id"] ?></a></td>
                            <td data-cell="Subject"><a href="/controllers/tickets/edit_ticket.php?id=<?= $row["id"]; ?>&nr=1"><?= $row["name"] ?></a></td>
                            <td data-cell="Request Detail"><?= limitChars($descriptionWithouthtml, 100) ?></td>
                            <td data-cell="Latest Note"><?= limitChars($latest_note_str, 150) ?></td>
                            <td data-cell="Location">
                                <?php
                                if ($row["location"] != null) {
                                    echo get_location_name_from_id($row["location"], false);
                                    // echo $row["location"];
                                }
                                if ($row["room"] != null) {
                                    echo '<br>RM ' . $row["room"];
                                }
                                ?>
                            </td>
                            <td data-cell="Category">
                                <?php
                                if ($row['request_type_id'] === '0') {
                                    echo "Other";
                                } else {
                                    $request_type_id = $row['request_type_id'];
                                    $request_type_query = "SELECT request_name FROM request_type WHERE request_id = ?";
                                    $request_type_query_result = $database->execute_query($request_type_query, [$request_type_id]);
                                    $request_type_name = mysqli_fetch_assoc($request_type_query_result)['request_name'];
                                    echo $request_type_name;
                                }
                                ?>
                            </td>
                            <td data-cell="Assigned Employee"><?= $row['employee'] ?></td>
                            <td data-cell="Current Status"><?= $row['status'] ?></td>
                            <td data-cell="Priority"><?= $priorityTypes[$row['priority']] ?></td>
                            <td data-cell="Created"><?= $row['created'] ?></td>
                            <td data-cell="Last Updated"><?= $row['last_updated'] ?></td>
                            <td data-cell="Due"><?= $row['due_date'] ?></td>
                        <?php
                        } elseif (isset($row['a_id'])) {
                        ?>
                            <td data-cell="ID"><a href="/controllers/tickets/archived_ticket_view.php?id=<?= $row["a_id"]; ?>"><?= $row["a_id"] ?></a></td>
                            <td data-cell="Subject"><a href="/controllers/tickets/archived_ticket_view.php?id=<?= $row["a_id"]; ?>"><?= $row["SUBJECT"] ?></a></td>
                            <td data-cell="Request Detail"><?= limitChars(html_entity_decode($row["QUESTION_TEXT"]), 150) ?></td>
                            <td data-cell="Latest Note">
                                <?php
                                $archived_ticket_id = substr($row["a_id"], 2);
                                $all_notes = [];

                                $tech_notes_query = "SELECT TECHNICIAN_ID, NOTE_TEXT, CREATION_DATE, HIDDEN, TECH_NOTE_DATE, BILLING_MINUTES FROM TECH_NOTE WHERE JOB_TICKET_ID = ?";
                                $stmt = mysqli_prepare($swdb, $tech_notes_query);
                                mysqli_stmt_bind_param($stmt, "i", $archived_ticket_id);
                                mysqli_stmt_execute($stmt);

                                $stmt_res = $stmt->get_result();


                                while ($tech_note_row = $stmt_res->fetch_array(MYSQLI_ASSOC)) {
                                    $note_text = $tech_note_row["NOTE_TEXT"];
                                    $tech_id = $tech_note_row["TECHNICIAN_ID"];
                                    $created_date = $tech_note_row["CREATION_DATE"];
                                    $hidden = $tech_note_row["HIDDEN"];
                                    $effective_date = $tech_note_row["TECH_NOTE_DATE"];
                                    $note_time = $tech_note_row["BILLING_MINUTES"];

                                    if ($note_time == null)
                                        $note_time = 0;

                                    $note_date = $effective_date;
                                    if ($created_date != $effective_date) {
                                        $note_date = $effective_date . "*";
                                    }
                                    $all_notes[] = [
                                        "creator" => get_tech_name_from_id($tech_id),
                                        "text" => $note_text,
                                        "date" => $note_date,
                                        "time" => $note_time,
                                        "hidden" => $hidden
                                    ];
                                }

                                mysqli_stmt_close($stmt);

                                $client_notes_query = "SELECT CLIENT_ID, TICKET_DATE, NOTE_TEXT FROM CLIENT_NOTE WHERE JOB_TICKET_ID = ?";
                                $stmt = mysqli_prepare($swdb, $client_notes_query);
                                mysqli_stmt_bind_param($stmt, "i", $archived_ticket_id);
                                mysqli_stmt_execute($stmt);

                                $stmt_res = $stmt->get_result();

                                while ($client_note_row = $stmt_res->fetch_array(MYSQLI_ASSOC)) {
                                    $client_id = $client_note_row["CLIENT_ID"];
                                    $note_text = $client_note_row["NOTE_TEXT"];
                                    $note_date = $client_note_row["TICKET_DATE"];
                                    if ($client_id != null) {
                                        $client_name = get_client_name_from_id($client_id);
                                    }
                                    $all_notes[] = [
                                        "creator" => $client_name,
                                        "text" => $note_text,
                                        "date" => $note_date,
                                        "time" => "—",
                                        "hidden" => false
                                    ];
                                }


                                usort($all_notes, 'sortByDate');

                                if (isset($all_notes[0]) && $all_notes[0]["text"] != null && $all_notes[0]["creator"] != null)
                                    echo $all_notes[0]["creator"] . ": " . $all_notes[0]["text"];

                                ?>
                            </td>
                            <td data-cell="Location">
                                <?php
                                if ($row["LOCATION_ID"] != null) {
                                    echo get_location_name_from_id($row["LOCATION_ID"], true);
                                }
                                if ($row["ROOM"] != null) {
                                    echo '<br>RM ' . $row["ROOM"];
                                }
                                ?>
                            </td>
                            <td data-cell="Category">
                                <?php
                                // $request_type_query = "SELECT request_name FROM request_type WHERE archived_request_ID = " . $row['PROBLEM_TYPE_ID'];
                                // $request_type_query_result = mysqli_query($database, $request_type_query);
                                // $request_type_name = mysqli_fetch_assoc($request_type_query_result)['request_name'];
                                //if ($request_type_name != null)
                                //    echo $request_type_name;
                                ?>
                            </td>
                            <td data-cell="Assigned Employee">
                                <?php if ($row['ASSIGNED_TECH_ID'] != null)
                                    echo get_tech_name_from_id($row['ASSIGNED_TECH_ID']);
                                else
                                    echo "unassigned";
                                ?></td>
                            <td data-cell="Current Status"></td>
                            <td data-cell="Priority"></td>
                            <td data-cell="Created"><?= $row['REPORT_DATE'] ?></td>
                            <td data-cell="Last Updated"><?= $row['LAST_UPDATED'] ?></td>
                            <td data-cell="Due">Date Close: <?= $row['CLOSE_DATE'] ?></td>
                        <?php
                        } else {
                            echo "Error";
                        }
                        ?>
                    </tr>

                <?php
                }

                ?>
            </tbody>
        </table>

    <?php
    } ?>
</article>
<?php include("footer.php"); ?>
<script src="/includes/js/pages/search_tickets.js?v=1.0.0" type="text/javascript"></script>