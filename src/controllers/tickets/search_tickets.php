<?php
require_once("block_file.php");
include("header.php");
require_once('helpdbconnect.php');
require_once('swdbconnect.php');
include("ticket_utils.php");

// Query the locations table to get the location information
$location_query = "SELECT sitenumber, location_name FROM locations";
$location_result = mysqli_query($database, $location_query);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    if (!empty($_GET)) {
        // Get the search terms from the form
        $search_id = isset($_GET['search_id']) ? mysqli_real_escape_string($database, $_GET['search_id']) : '';
        $search_name = isset($_GET['search_name']) ? mysqli_real_escape_string($database, $_GET['search_name']) : '';
        $search_location = isset($_GET['search_location']) ? mysqli_real_escape_string($database, $_GET['search_location']) : '';
        $search_employee = isset($_GET['search_employee']) ? mysqli_real_escape_string($database, $_GET['search_employee']) : '';
        $search_client = isset($_GET['search_client']) ? mysqli_real_escape_string($database, $_GET['search_client']) : '';
        $search_status = isset($_GET['search_status']) ? mysqli_real_escape_string($database, $_GET['search_status']) : '';


        // Construct the SQL query based on the selected search options
        $ticket_query = "SELECT * FROM tickets WHERE 1=1";
        if (!empty($search_id)) {
            $search_id = intval($search_id);
            $ticket_query .= " AND id LIKE '$search_id'";
        }
        if (!empty($search_name)) {
            $ticket_query .= " AND (name LIKE '%$search_name%' OR description LIKE '%$search_name%')";
        }
        if (!empty($search_location)) {
            $ticket_query .= " AND location LIKE '%$search_location%'";
        }
        if (!empty($search_employee)) {
            $ticket_query .= " AND employee LIKE '%$search_employee%'";
        }
        if (!empty($search_client)) {
            $ticket_query .= " AND client LIKE '%$search_client%'";
        }
        if (!empty($search_status)) {
            $ticket_query .= " AND status LIKE '%$search_status%'";
        }

        // Query the archived_location_id values for the given sitenumber
        $archived_location_ids = array();
        $arch_location_query = "SELECT archived_location_id FROM locations WHERE sitenumber = '$search_location'";
        $arch_location_result = $database->query($arch_location_query);
        if ($arch_location_result->num_rows > 0) {
            while ($arch_row = $arch_location_result->fetch_assoc()) {
                $archived_location_ids[] = $arch_row['archived_location_id'];
            }
        }

        // Construct the SQL query for the old ticket database
        $old_ticket_query = "SELECT CONCAT('A-', JOB_TICKET_ID) AS a_id,PROBLEM_TYPE_ID,SUBJECT,QUESTION_TEXT,REPORT_DATE,LAST_UPDATED,JOB_TIME,ASSIGNED_TECH_ID,ROOM,LOCATION_ID,STATUS_TYPE_ID FROM whd.job_ticket WHERE 1=1";

        // map old system status ids to our current system
        switch ($search_status) {
            case 'open':
                $search_status = 1;
                break;
            case 'closed':
                $search_status = 3;
                break;
            case 'resolved':
                $search_status = 5;
                break;
            case 'pending':
                $search_status = 7;
                break;
            case 'maintenance':
                $search_status = 11;
                break;
            case 'vendor':
                $search_status = 12;
                break;
            default:
                $search_status = null;
        }
        if (!empty($search_id)) {
            $search_id = intval($search_id);
            $old_ticket_query .= " AND JOB_TICKET_ID LIKE '$search_id'";
        }
        if (!empty($search_name)) {
            $old_ticket_query .= " AND (SUBJECT LIKE '%$search_name%' OR QUESTION_TEXT LIKE '%$search_name%')";
        }
        if (!empty($search_location)) {
            $old_ticket_query .= " AND LOCATION_ID IN (" . implode(",", $archived_location_ids) . ")";
        }
        if (!empty($search_employee)) {
            $old_ticket_query .= " AND ASSIGNED_TECH_ID LIKE '%$search_employee%'";
        }
        if (!empty($search_client)) {
            $old_ticket_query .= " AND CLIENT_ID LIKE '%$search_client%'";
        }
        if (!empty($search_status)) {
            $old_ticket_query .= " AND STATUS_TYPE_ID  LIKE '%$search_status%'";
        }

        // Execute the SQL query to search for matching tickets
        $ticket_result = mysqli_query($database, $ticket_query);
        $old_ticket_result = mysqli_query($swdb, $old_ticket_query);


        // Combine the results from both queries into a single array
        $combined_results = array();
        while ($row = mysqli_fetch_assoc($ticket_result)) {
            $combined_results[] = $row;
        }
        while ($row = mysqli_fetch_assoc($old_ticket_result)) {
            $combined_results[] = $row;
        }
    }
}

// Fetch the list of usernames from the users table
$usernamesQuery = "SELECT username,is_tech FROM users";
$usernamesResult = mysqli_query($database, $usernamesQuery);

if (!$usernamesResult) {
    die('Error fetching usernames: ' . mysqli_error($database));
}

// Store the usernames in an array
$usernames = array();
$techusernames = array();
while ($usernameRow = mysqli_fetch_assoc($usernamesResult)) {

    if ($usernameRow['is_tech'] == 1) {
        $techusernames[] = $usernameRow['username'];
    } else {
        $usernames[] = $usernameRow['username'];
    }
}

asort($techusernames);

// TODO could cache these
function get_client_name_from_id(string $client_sw_id)
{
    global $swdb;

    $client_name_query = "SELECT FIRST_NAME, LAST_NAME FROM client WHERE CLIENT_ID = '$client_sw_id'";
    $client_name_result = mysqli_query($swdb, $client_name_query);
    $client_name_data = mysqli_fetch_assoc($client_name_result);
    $client_name = trim($client_name_data["FIRST_NAME"]) . " " . trim($client_name_data["LAST_NAME"]);

    return $client_name;
}

// TODO could cache these
function get_tech_name_from_id(string $tech_sw_id)
{
    global $swdb;

    $tech_name_query = "SELECT FIRST_NAME, LAST_NAME FROM tech WHERE CLIENT_ID = '$tech_sw_id'";
    $tech_name_result = mysqli_query($swdb, $tech_name_query);
    $tech_name_data = mysqli_fetch_assoc($tech_name_result);
    $tech_name = trim($tech_name_data["FIRST_NAME"]) . " " . trim($tech_name_data["LAST_NAME"]);

    return $tech_name;
}

// TODO could cache these
function get_location_name_from_id(string $location_sw_id)
{
    global $swdb;
    $location_name_query = "SELECT LOCATION_NAME FROM location WHERE LOCATION_ID = '$location_sw_id'";
    $location_name_result = mysqli_query($swdb, $location_name_query);
    $location_name_data = mysqli_fetch_assoc($location_name_result);
    $location_name = trim($location_name_data["LOCATION_NAME"]);

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
            <label for="search_name">Name or Description:</label>
            <input type="text" class="form-control" id="search_name" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>">
        </div>
        <div class="form-group">
            <label for="search_location">Location:</label>
            <!-- <input type="text" class="form-control" id="search_location" name="search_location" value="<?php echo htmlspecialchars($search_location); ?>"> -->
            <select id="search_location" name="search_location">
                <option value="" selected></option>
                <?php
                // Loop through the results and create an option for each site
                while ($locations = mysqli_fetch_assoc($location_result)) {
                    $selected = '';
                    if (isset($locations['sitenumber']) && isset($row['location']) && $locations['sitenumber'] == $row['location']) {
                        $selected = 'selected';
                    }
                ?>
                    <option value="<?= isset($locations['sitenumber']) ? $locations['sitenumber'] : '' ?>" <?= isset($search_location) && $search_location === $locations['sitenumber'] ? 'selected' : '' ?>><?= isset($locations['location_name']) ? $locations['location_name'] : '' ?></option>
                <?php
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="search_employee">Tech:</label>
            <!-- <input type="text" class="form-control" id="search_employee" name="search_employee" value="<?php echo htmlspecialchars($search_employee); ?>"> -->
            <select id="search_employee" name="search_employee">
                <option value="" selected></option>
                <?php foreach ($techusernames as $techuser) : ?>
                    <option value="<?= $techuser ?>" <?= $search_employee === $techuser ? 'selected' : '' ?>><?= $techuser ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="search_client">Client:</label>
            <!-- <input type="text" class="form-control" id="search_client" name="search_client" value="<?php echo htmlspecialchars($search_client); ?>"> -->
            <select id="search_client" name="search_client">
                <option value="" selected></option>
                <?php foreach ($usernames as $username) : ?>

                    <option value="<?= $username ?>" <?= $search_client === $username ? 'selected' : '' ?>><?= $username ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="search_status">Status:</label>
            <select id="status" name="search_status">
                <option value="" selected></option>
                <option value="open" <?= ($search_status == 'open') ? ' selected' : '' ?>>Open</option>
                <option value="closed" <?= ($search_status == 'closed') ? ' selected' : '' ?>>Closed</option>
                <option value="resolved" <?= ($search_status == 'resolved') ? ' selected' : '' ?>>Resolved</option>
                <option value="pending" <?= ($search_status == 'pending') ? ' selected' : '' ?>>Pending</option>
                <option value="vendor" <?= ($search_status == 'vendor') ? ' selected' : '' ?>>Vendor</option>
                <option value="maintenance" <?= ($search_status == 'maintenance') ? ' selected' : '' ?>>Maintenance</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <button type="reset" id="resetBtn" class="btn btn-secondary">Reset</button>
    </form>


    <h2>Search Results</h2>
    <table class="ticketsTable data-table">
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
                        $latest_note_str = $creator . ': ' . strip_tags(html_entity_decode(html_entity_decode($note_data)));
                        log_app(LOG_INFO, $latest_note_str);
                    }

                    if (isset($row['id'])) {
                        $descriptionWithouthtml = strip_tags(html_entity_decode($row["description"]));
                    ?>
                        <td data-cell="ID"><a href="/controllers/tickets/edit_ticket.php?id=<?= $row["id"]; ?>&nr=1"><?= $row["id"] ?></a></td>
                        <td data-cell="Subject"><a href="/controllers/tickets/edit_ticket.php?id=<?= $row["id"]; ?>&nr=1"><?= $row["name"] ?></a></td>
                        <td data-cell="Request Detail"><?= limitChars($descriptionWithouthtml, 100) ?></td>
                        <td data-cell="Latest Note"><?= limitChars($latest_note_str, 100) ?></td>
                        <td data-cell="Location">
                            <?php
                            // Query the sites table to get the location name
                            // $location_query = "SELECT location_name FROM locations WHERE sitenumber = " . $row["location"];
                            // $location_result = mysqli_query($database, $location_query);
                            // $location_name = mysqli_fetch_assoc($location_result)['location_name'];

                            // // Display the location name and room number
                            // echo $location_name . '<br><br>RM ' . $row['room'];
                            ?>
                        </td>
                        <td data-cell="Category">
                            <?php
                            if ($row['request_type_id'] === '0') {
                                echo "Other";
                            } else {
                                $request_type_query = "SELECT request_name FROM request_type WHERE request_id = " . $row['request_type_id'];
                                $request_type_query_result = mysqli_query($database, $request_type_query);
                                $request_type_name = mysqli_fetch_assoc($request_type_query_result)['request_name'];
                                echo $request_type_name;
                            }
                            ?>
                        </td>
                        <td data-cell="Assigned Employee"><?= $row['employee'] ?></td>
                        <td data-cell="Current Status"><?= $row['status'] ?></td>
                        <td data-cell="Created"><?= $row['created'] ?></td>
                        <td data-cell="Last Updated"><?= $row['last_updated'] ?></td>
                        <?php
                        // Get the priority value from the ticket row
                        $priority = $row['priority'];
                        // Calculate the due date by adding the priority days to the created date
                        $created_date = new DateTime($row['created']);
                        $due_date = clone $created_date;
                        $due_date->modify("+{$priority} weekdays");

                        // Check if the due date falls on a weekend or excluded date
                        while (isWeekend($due_date)) {
                            $due_date->modify("+1 day");
                        }
                        $count = hasExcludedDate($created_date->format('Y-m-d'), $due_date->format('Y-m-d'));
                        if ($count > 0) {
                            $due_date->modify("{$count} day");
                        }
                        // Format the due date as a string
                        $due_date = $due_date->format('Y-m-d');
                        ?>
                        <td data-cell="Due"><?= $due_date ?></td>
                    <?php
                    } elseif (isset($row['a_id'])) {
                    ?>
                        <td data-cell="ID"><a href="/controllers/tickets/archived_ticket_view.php?id=<?= $row["a_id"]; ?>"><?= $row["a_id"] ?></a></td>
                        <td data-cell="Subject"><a href="/controllers/tickets/archived_ticket_view.php?id=<?= $row["a_id"]; ?>"><?= $row["SUBJECT"] ?></a></td>
                        <td data-cell="Request Detail"><?= limitChars(html_entity_decode($row["QUESTION_TEXT"]), 100) ?></td>
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

                                $all_notes[] = [
                                    "creator" => get_client_name_from_id($client_id),
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
                            // Query the sites table to get the location name
                            // $location_query = "SELECT location_name FROM locations WHERE archived_location_id = " . $row["LOCATION_ID"];
                            // $location_result = mysqli_query($database, $location_query);
                            // $location_data = mysqli_fetch_assoc($location_result);

                            // TODO support archived tickets
                            // if ($location_data != null) {
                            //     $location_name = $location_data['location_name'];

                            //     // Display the location name and room number
                            //     echo $location_name . '<br><br>RM ' . $row['ROOM'];
                            // }
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
                        <td data-cell="Created"><?= $row['REPORT_DATE'] ?></td>
                        <td data-cell="Last Updated"><?= $row['LAST_UPDATED'] ?></td>
                        <td data-cell="Due"></td>
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
</article>
<?php include("footer.php"); ?>