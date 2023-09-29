<?php
include("../../includes/header.php");

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
require_once('../../includes/helpdbconnect.php');
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
$ifasid = $row['ifasid'];
$last_login = $row['last_login'];
$can_view_tickets = $row['can_view_tickets'];
$can_create_tickets = $row['can_create_tickets'];
$can_edit_tickets = $row['can_edit_tickets'];
$can_delete_tickets = $row['can_delete_tickets'];

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
Last Login: <?= $last_login ?><br>
Username: <?= $username ?><br>
<form action="update_user.php" method="post">
    <input type="hidden" name="id" value="<?= $user_id ?>">
    <label for="firstname">First Name:</label>
    <input type="text" id="firstname" name="firstname" value="<?= $firstname ?>"><br>
    <label for="lastname">Last Name:</label>
    <input type="text" id="lastname" name="lastname" value="<?= $lastname ?>"><br>
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?= $email ?>"><br>
    <label for="is_admin">Is Admin:</label>
    <input type="checkbox" id="is_admin" name="is_admin" <?= $is_admin == 1 ? 'checked' : '' ?>><br>
    <label for="ifasid">Employee ID:</label>
    <input type="text" id="ifasid" name="ifasid" value="<?= $ifasid ?>"><br>
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

<?php include("../../includes/footer.php"); ?>