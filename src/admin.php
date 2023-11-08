<?php
include("header.php");
require("status_popup.php");
if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    echo 'You do not have permission to view this page.';
    exit;
}

require_once('helpdbconnect.php');
require(from_root("/controllers/tickets/ticket_utils.php"));

// Execute the SELECT query to retrieve all users from the users table
$users_query = "SELECT * FROM users ORDER BY username ASC";
$user_result = mysqli_query($database, $users_query);
// Check if the query was successful
if (!$user_result) {
    die("Query failed: " . mysqli_error($conn));
}

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

    $status_popup = new StatusPopup();
    $status_popup->message_body = $_SESSION['current_status'];
    $status_popup->message_title = $status_title;
    $status_popup->alert_type = $status_type;

    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}
?>
<h1>Admin</h1><br>
<h2>Reports</h2><br>

<h3>Open Tickets</h4>
    <table>
        <tr>
            <th>Tech</th>
            <th>Assigned Tickets</th>
        </tr>

        <?php
        // not super clean
        // Query open tickets based on tech
        $query = "SELECT employee FROM tickets WHERE status NOT IN ('closed', 'resolved')";
        $query_result = mysqli_query($database, $query);

        $tech_count = [];

        while ($row = mysqli_fetch_assoc($query_result)) {
            $emp = $row["employee"];
            if ($emp == null || $emp == "")
                $emp = "unassigned";

            if (!isset($tech_count[$emp]))
                $tech_count[$emp] = 1;
            else
                $tech_count[$emp]++;
        }
        arsort($tech_count);
        foreach ($tech_count as $name => $count) {
        ?>
            <tr>
                <td data-cell="Employee"><?= $name ?></td>
                <td data-cell="Ticket Count"><?= $count ?></td>
            </tr>
        <?php
        }
        ?>
    </table><br>

    <h2>All Users</h2>
    <table class="allUsers data-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Is Admin</th>
                <th>Is Tech</th>
                <th>Employee ID</th>
                <th>Last Login</th>
            </tr>
        </thead>
        <tbody>
            <?php // Display the results in an HTML table
            while ($user_row = mysqli_fetch_assoc($user_result)) {

            ?>
                <tr>
                    <td data-cell="User Name"><a href="controllers/users/manage_user.php?id=<?= $user_row['id'] ?>"><?= $user_row['username'] ?></a></td>
                    <td data-cell="First Name"><?= ucwords(strtolower($user_row['firstname'])) ?></td>
                    <td data-cell="Last Name"><?= ucwords(strtolower($user_row['lastname'])) ?></td>
                    <td data-cell="Email"><?= $user_row['email'] ?></td>
                    <td data-cell="Is an Admin"><?= ($user_row['is_admin'] == 1 ? 'Yes' : 'No') ?></td>
                    <td data-cell="Is an Tech"><?= ($user_row['is_tech'] == 1 ? 'Yes' : 'No') ?></td>
                    <td data-cell="Employee ID"><?= $user_row['ifasid'] ?></td>
                    <td data-cell="Last Login"><?= $user_row['last_login'] ?></td>
                </tr>
            <?php
            }
            ?>
        </tbody>


    </table>

    <h1>Add Exclude Day</h1>
    <form method="POST" action="/controllers/admin/exclude_days.php">
        <div>
            <label for="exclude_day">Exclude Day:</label>
            <input type="date" id="exclude_day" name="exclude_day">
        </div>
        <button type="submit">Add Exclude Day</button>
    </form>
    <h1>Exclude Days</h1>
    <?php
    // Fetch the exclude days from the database
    $exclude_query = "SELECT * FROM exclude_days ORDER BY exclude_day";
    $exclude_result = mysqli_query($database, $exclude_query);
    ?>
    <table class="exclude_days data-table">
        <thead>
            <tr>
                <th>Exclude Day</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php while ($exclude_row = mysqli_fetch_assoc($exclude_result)) : ?>
                <tr>
                    <td data-cell="Excluded Day"><?= $exclude_row['exclude_day'] ?></td>
                    <td data-cell="Remove Excluded Day"><a href="/controllers/admin/delete_exclude_day.php?id=<?= $exclude_row['id'] ?>">Delete</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table><br>
    <h2>Merge Tickets</h2>
    <form method="POST" action="/controllers/tickets/merge_tickets_handler.php">
        <input type="hidden" name="username" value="<?= $_SESSION['username'] ?>">
        Host Ticket ID: <input type="text" id="ticket_id_host" name="ticket_id_host" value=""><br>
        Source Ticket ID:<input type="text" id="ticket_id_source" name="ticket_id_source" value=""><br>
        <button type="submit">Merge</button><br>
    </form>
    <h2>Unassigned Tickets</h2>
    <table class="ticketsTable data-table">
        <thead>
            <tr>
                <th class="tID">ID</th>
                <th>Subject</th>
                <th>Request Detail</th>
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
            // Execute the SQL query
            $ticket_query = "SELECT *
        FROM tickets
        WHERE employee IS NULL OR employee = 'unassigned'
        ORDER BY id ASC";

            $ticket_result = mysqli_query($database, $ticket_query);
            while ($ticket_row = mysqli_fetch_assoc($ticket_result)) {
            ?>
                <tr>
                    <td data-cell="ID"><a href="/controllers/tickets/edit_ticket.php?id=<?= $ticket_row["id"]; ?>"><?= $ticket_row["id"] ?></a></td>
                    <td data-cell="Subject"><a href="/controllers/tickets/edit_ticket.php?id=<?= $ticket_row["id"]; ?>"><?= $ticket_row["name"] ?></a></td>
                    <td data-cell="Request Detail"><?= limitChars(html_entity_decode($ticket_row["description"]), 100) ?></td>
                    <td data-cell="Location">
                        <?php
                        // Query the sites table to get the location name
                        $location_query = "SELECT location_name FROM locations WHERE sitenumber = " . $ticket_row["location"];
                        $location_result = mysqli_query($database, $location_query);
                        $location_name = mysqli_fetch_assoc($location_result)['location_name'];

                        // Display the location name and room number
                        echo $location_name . '<br><br>RM ' . $ticket_row['room'];
                        ?>
                    </td>
                    <td data-cell="Category">
                        <?php
                        if ($ticket_row['request_type_id'] === '0') {
                            echo "Other";
                        } else {
                            $request_type_query = "SELECT request_name FROM request_type WHERE request_id = " . $ticket_row['request_type_id'];
                            $request_type_query_result = mysqli_query($database, $request_type_query);
                            $request_type_name = mysqli_fetch_assoc($request_type_query_result)['request_name'];
                            echo $request_type_name;
                        }
                        ?>
                    </td>
                    <td data-cell="Assigned Employee"><?php if ($ticket_row['employee'] == NULL) {
                                                            echo 'unassigned';
                                                        } else {
                                                            echo $ticket_row['employee'];
                                                        }  ?></td>
                    <td data-cell="Current Status"><?= $ticket_row['status'] ?></td>
                    <td data-cell="Created"><?= $ticket_row['created'] ?></td>
                    <td data-cell="Last Updated"><?= $ticket_row['last_updated'] ?></td>
                    <?php
                    // Get the priority value from the ticket row
                    $priority = $ticket_row['priority'];
                    // Calculate the due date by adding the priority days to the created date
                    $created_date = new DateTime($ticket_row['created']);
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
                </tr>
            <?php
            } // end while
            ?>
        </tbody>
    </table>
    <?php include("footer.php"); ?>