<?php
include("header.php");

if ($_SESSION['permissions']['is_supervisor'] != 1) {
    echo 'You do not have permission to view this page.';
    exit;
}

// Check if the user ID is set
if (!isset($_GET['id'])) {
    die("User ID not set");
}
require_once('helpdbconnect.php');
require_once("tickets_template.php");

// Retrieve the user with the corresponding ID
$user_id = $_GET['id'];
// User is an admin
$result = HelpDB::get()->execute_query("
    SELECT u.*, us.*
    FROM users u
    LEFT JOIN user_settings us ON u.id = us.user_id
    WHERE u.id = ?
", [$user_id]);
// Check if the query was successful
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
// Retrieve the user data
$row = mysqli_fetch_assoc($result);
$username = $row['username'];
$firstname = $row['firstname'];
$lastname = $row['lastname'];
$email = $row['email'];
$is_admin = $row['is_admin'];
$is_tech = $row['is_tech'];
$is_intern = $row['is_intern'];
$intern_site = $row['intern_site'];
$is_supervisor = $row['is_supervisor'];
$is_loc_man = $row['is_location_manager'];
$ifasid = $row['ifasid'];
$last_login = $row['last_login'];
$can_view_tickets = $row['can_view_tickets'];
$can_create_tickets = $row['can_create_tickets'];
$can_edit_tickets = $row['can_edit_tickets'];
$supervisor_username = $row['supervisor_username'];
$man_location = $row['location_manager_sitenumber'];
?>
<?php
// Check if a success message is set
if (isset($_SESSION['user_updated'])) {
    echo '<div class="success-message">' . $_SESSION['user_updated'] . '</div>';

    // Unset the success message to clear it
    unset($_SESSION['user_updated']);
}
?>
<h2>User Information</h2>
Last Login: <?= $last_login ?><br>
Username: <?= $username ?><br>

<?php
if ($_SESSION['permissions']['is_admin'] != 1) {
    echo "Employee ID: $ifasid<br>";
    echo "First Name: $firstname<br>";
    echo "Last Name: $lastname<br>";
    echo "Email: $email<br>";
}

if ($_SESSION['permissions']['is_admin'] == 1) {

    // Query to get all supervisors
    $supervisors_result = HelpDB::get()->execute_query("
        SELECT u.firstname, u.lastname, u.username 
        FROM users u
        LEFT JOIN user_settings us ON u.id = us.user_id
        WHERE us.is_supervisor = 1
    ");

    // Check if the query was successful
    if (!$supervisors_result) {
        die("Query failed: " . mysqli_error(HelpDB::get()));
    }
?>
    <h1>Manage User: <?= ucwords(strtolower($firstname)) . ' ' . ucwords(strtolower($lastname)) ?></h1>

    <form action="update_user.php" method="post">
        <input type="hidden" name="id" value="<?= $user_id ?>">
        <input type="hidden" name="username" value="<?= $username ?>">
        <label for="firstname">First Name:</label>
        <input type="text" id="firstname" name="firstname" value="<?= $firstname ?>"><br>
        <label for="lastname">Last Name:</label>
        <input type="text" id="lastname" name="lastname" value="<?= $lastname ?>"><br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= $email ?>"><br>
        <label for="ifasid">Employee ID:</label>
        <input type="text" id="ifasid" name="ifasid" value="<?= $ifasid ?>"><br>
        <label for="intern_site">Intern Site:</label>
        <input type="text" id="intern_site" name="intern_site" value="<?= $intern_site ?>"><br>
        <!-- Supervisor dropdown -->
        <label for="supervisor">Supervisor:</label>
        <select name="supervisor" id="supervisor">
            <option value="" selected></option>
            <?php
            // Loop through the supervisors and create an option for each one
            while ($supervisor = mysqli_fetch_assoc($supervisors_result)) {
                // Determine whether this option should be selected
                $selected = $supervisor['username'] == $supervisor_username ? 'selected' : '';
                echo '<option value="' . $supervisor['username'] . '" ' . $selected . '>' . $supervisor['firstname'] . ' ' . $supervisor['lastname'] . '</option>';
            }
            ?>
        </select><br>

        <h3>Roles</h3>
        <div class="userPermissions">
            <div>
                <label for="is_admin">Is Admin - Can Manage Users, Exclude Days</label>
                <input type="checkbox" id="is_admin" name="is_admin" <?= $is_admin == 1 ? 'checked' : '' ?>>
            </div>

            <div>
                <label for="is_supervisor">Is Supervisor - Has Dashboard Screen, Can Be Assigned Subordinates</label>
                <input type="checkbox" id="is_supervisor" name="is_supervisor" <?= $is_supervisor == 1 ? 'checked' : '' ?>>
            </div>

            <div>
                <label for="is_tech">Is Tech - Can Be Assigned Tickets</label>
                <input type="checkbox" id="is_tech" name="is_tech" <?= $is_tech == 1 ? 'checked' : '' ?>>
            </div>

            <div>
                <label for="is_intern">Is Intern - Can Work On Intern Marked Tickets</label>
                <input type="checkbox" id="is_intern" name="is_intern" <?= $is_intern == 1 ? 'checked' : '' ?>>
            </div>

            <div>
                <label for="is_loc_man">Is Location Manager - Has menu to see tickets at location</label>
                <input type="checkbox" id="is_loc_man" name="is_loc_man" <?= $is_loc_man == 1 ? 'checked' : '' ?>>
            </div>
        </div>
        <?php
        if ($is_loc_man == 1) {
        ?>

            <label for="man_location">Manage Location:</label>
            <select id="man_location" name="man_location">
                <option value="" selected></option>
                <?php
                // Query the locations table to get the departments
                $department_result = HelpDB::get()->execute_query("SELECT * FROM locations WHERE is_department = TRUE ORDER BY location_name ASC");

                // Create a "Department" optgroup and create an option for each department
                echo '<optgroup label="Department">';
                while ($locations = mysqli_fetch_assoc($department_result)) {
                    $selected = '';
                    if ($locations['sitenumber'] == $man_location) {
                        $selected = 'selected';
                    }
                    echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
                }
                echo '</optgroup>';

                // Query the locations table to get the locations
                $location_result = HelpDB::get()->execute_query("SELECT * FROM locations WHERE is_department = FALSE ORDER BY location_name ASC");

                // Create a "Location" optgroup and create an option for each location
                echo '<optgroup label="Location">';
                while ($locations = mysqli_fetch_assoc($location_result)) {
                    $selected = '';
                    if ($locations['sitenumber'] == $man_location) {
                        $selected = 'selected';
                    }
                    echo '<option value="' . $locations['sitenumber'] . '" ' . $selected . '>' . $locations['location_name'] . '</option>';
                }
                echo '</optgroup>';
                ?>
            </select>
        <?php
        } else {
        ?>
            <input type="hidden" id="man_location" name="man_location" value="">
        <?php
        }

        ?>


        <h3>Permissions</h3>
        <div class="userPermissions">
            <div>
                <label for="can_view_tickets">Can View Tickets:</label>
                <input type="checkbox" id="can_view_tickets" name="can_view_tickets" <?= $can_view_tickets == 1 ? 'checked' : '' ?>>
            </div>
            <div>
                <label for="can_create_tickets">Can Create Tickets:</label>
                <input type="checkbox" id="can_create_tickets" name="can_create_tickets" <?= $can_create_tickets == 1 ? 'checked' : '' ?>>
            </div>
            <div>
                <label for="can_edit_tickets">Can Edit Tickets:</label>
                <input type="checkbox" id="can_edit_tickets" name="can_edit_tickets" <?= $can_edit_tickets == 1 ? 'checked' : '' ?>>
            </div>
        </div>

        <input type="submit" value="Update User">
    </form>
<?php
}






// Query the locations table to get the location information
// $location_query = "SELECT sitenumber, location_name FROM locations";
// $location_result = mysqli_query(HelpDB::get(), $location_query);


// SQL query for users Tickets
$ticket_query = <<<STR
SELECT *
FROM tickets
WHERE status NOT IN ('Closed', 'Resolved')
AND employee = ?
ORDER BY id ASC
STR;

$ticket_result = HelpDB::get()->execute_query($ticket_query, [$username]);
$client_tickets = mysqli_fetch_all($ticket_result, MYSQLI_ASSOC);

?>



<h2>Tickets Assigned to <?= $username; ?></h2>

<?php
// display tickets that are assigned to the user.
display_tickets_table($client_tickets, HelpDB::get());
?>

<?php include("footer.php"); ?>