<?php
include("includes/header.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    echo 'You do not have permission to view this page.';
    exit;
}
require_once('includes/helpdbconnect.php');
include("controllers/tickets/ticket_utils.php");
// Execute the SELECT query to retrieve all users from the users table
$users_query = "SELECT * FROM users";
$user_result = mysqli_query($database, $users_query);
// Check if the query was successful
if (!$user_result) {
    die("Query failed: " . mysqli_error($conn));
}
?>
<h1>Admin</h1>
<h2>Users</h2>
<table>
    <tr>
        <th>Username</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Is Admin</th>
        <th>Employee ID</th>
        <th>Last Login</th>
    </tr>
    <tr>
        <?php // Display the results in an HTML table
        while ($user_row = mysqli_fetch_assoc($user_result)) {
        ?>
    <tr>
        <td><a href="controllers/users/manage_user.php?id=<?= $user_row['id'] ?>"><?= $user_row['username'] ?></a></td>
        <td><?= ucwords(strtolower($user_row['firstname'])) ?></td>
        <td><?= ucwords(strtolower($user_row['lastname'])) ?></td>
        <td><?= $user_row['email'] ?></td>
        <td><?= ($user_row['is_admin'] == 1 ? 'Yes' : 'No') ?></td>
        <td><?= $user_row['ifasid'] ?></td>
        <td><?= $user_row['last_login'] ?></td>
    </tr>
<?php
        }
?>
</tr>
</table>

<h1>Add Exclude Day</h1>

<form method="POST" action="<?= $root_domain ?>/controllers/admin/exclude_days.php">
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
                <td><?= $exclude_row['exclude_day'] ?></td>
                <td><a href="<?= $root_domain ?>/controllers/admin/delete_exclude_day.php?id=<?= $exclude_row['id'] ?>">Delete</a></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<h2>All Tickets</h2>
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
                <td data-cell="Assigned Employee"><?= $ticket_row['employee'] ?></td>
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


<?php include("includes/footer.php"); ?>