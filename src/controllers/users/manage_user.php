<?php
include("header.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admiin

    // need to add a permission for "edit users"
    echo 'You do not have permission to view this page.';
    exit;
}

// Check if the user ID is set
if (!isset($_GET['id'])) {
    die("User ID not set");
}
require_once('helpdbconnect.php');
// Retrieve the user with the corresponding ID
$user_id = $_GET['id'];
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($database, $query);
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
$is_supervisor = $row['is_supervisor'];
$is_loc_man = $row['is_location_manager'];
$is_field_tech = $row['is_field_tech'];
$ifasid = $row['ifasid'];
$last_login = $row['last_login'];
$can_view_tickets = $row['can_view_tickets'];
$can_create_tickets = $row['can_create_tickets'];
$can_edit_tickets = $row['can_edit_tickets'];
$can_delete_tickets = $row['can_delete_tickets'];
$supervisor_username = $row['supervisor_username'];
$man_location = $row['location_manager_sitenumber'];


// Query to get all supervisors
$supervisors_query = "SELECT firstname, lastname, username FROM users WHERE is_supervisor = 1";
$supervisors_result = mysqli_query($database, $supervisors_query);

// Check if the query was successful
if (!$supervisors_result) {
    die("Query failed: " . mysqli_error($database));
}

// Query the locations table to get the location information
$location_query = "SELECT sitenumber, location_name FROM locations";
$location_result = mysqli_query($database, $location_query);

// Close the database connection
mysqli_close($database);

?>
<h1>Manage User: <?= ucwords(strtolower($firstname)) . ' ' . ucwords(strtolower($lastname)) ?></h1>
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
    <!-- Supervisor dropdown -->
    <label for="supervisor">Supervisor:</label>
    <select name="supervisor" id="supervisor">
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
    <label for="is_admin">Is Admin:</label>
    <input type="checkbox" id="is_admin" name="is_admin" <?= $is_admin == 1 ? 'checked' : '' ?>><br>

    <label for="is_supervisor">Is Supervisor:</label>
    <input type="checkbox" id="is_supervisor" name="is_supervisor" <?= $is_supervisor == 1 ? 'checked' : '' ?>><br>

    <label for="is_tech">Is Tech:</label>
    <input type="checkbox" id="is_tech" name="is_tech" <?= $is_tech == 1 ? 'checked' : '' ?>><br>

    <label for="is_field_tech">Is Field Tech:</label>
    <input type="checkbox" id="is_field_tech" name="is_field_tech" <?= $is_field_tech == 1 ? 'checked' : '' ?>><br>

    <label for="is_loc_man">Is Location Manager:</label>
    <input type="checkbox" id="is_loc_man" name="is_loc_man" <?= $is_loc_man == 1 ? 'checked' : '' ?>><br>
    <?php
    if ($is_loc_man == 1) {
    ?>

        <label for="man_location">Manage Location:</label>
        <select id="man_location" name="man_location">
            <option value="" selected></option>
            <?php
            // Loop through the results and create an option for each site
            while ($locations = mysqli_fetch_assoc($location_result)) {
               
            ?>
                <option value="<?= $locations['sitenumber'] ?>" <?= $man_location === $locations['sitenumber'] ? 'selected' : '' ?>><?= $locations['location_name'] ?></option>
            <?php
            }
            ?>
        </select>


    <?php
    }
    ?>


    <h3>Permissions</h3>
    <label for="can_view_tickets">Can View Tickets:</label>
    <input type="checkbox" id="can_view_tickets" name="can_view_tickets" <?= $can_view_tickets == 1 ? 'checked' : '' ?>><br>
    <label for="can_create_tickets">Can Create Tickets:</label>
    <input type="checkbox" id="can_create_tickets" name="can_create_tickets" <?= $can_create_tickets == 1 ? 'checked' : '' ?>><br>
    <label for="can_edit_tickets">Can Edit Tickets:</label>
    <input type="checkbox" id="can_edit_tickets" name="can_edit_tickets" <?= $can_edit_tickets == 1 ? 'checked' : '' ?>><br>
    <label for="can_delete_tickets">Can Delete Tickets:</label>
    <input type="checkbox" id="can_delete_tickets" name="can_delete_tickets" <?= $can_delete_tickets == 1 ? 'checked' : '' ?>><br>

    <input type="submit" value="Update">
</form>

<?php include("footer.php"); ?>