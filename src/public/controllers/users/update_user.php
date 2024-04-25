<?php
require_once('init.php');
require_once('helpdbconnect.php');
require_once('functions.php');
// Check if the user ID is set
if (!isset($_POST['id'])) {
    die("User ID not set");
}
$modified_by = $_SESSION['username'];

// Retrieve the user ID and data from the form submission
$user_id = $_POST['id'];
$firstname = trim(htmlspecialchars($_POST['firstname']));
$username = trim(htmlspecialchars($_POST['username']));
$lastname = trim(htmlspecialchars($_POST['lastname']));
$email = trim(htmlspecialchars($_POST['email']));
$ifasid = trim(htmlspecialchars($_POST['ifasid']));
$is_admin = isset($_POST['is_admin']) ? 1 : 0;
$is_tech = isset($_POST['is_tech']) ? 1 : 0;
$is_supervisor = isset($_POST['is_supervisor']) ? 1 : 0;
$can_view_tickets = isset($_POST['can_view_tickets']) ? 1 : 0;
$can_create_tickets = isset($_POST['can_create_tickets']) ? 1 : 0;
$can_edit_tickets = isset($_POST['can_edit_tickets']) ? 1 : 0;
$can_delete_tickets = isset($_POST['can_delete_tickets']) ? 1 : 0;
$is_loc_man = isset($_POST['is_loc_man']) ? 1 : 0;
$supervisor_username = trim(htmlspecialchars($_POST['supervisor']));
$man_location = trim(htmlspecialchars($_POST['man_location']));

// Update the user data in the database
$query = "UPDATE users SET firstname = ?, lastname = ?, email = ?, ifasid = ?, is_admin = ?, is_tech = ?,is_supervisor = ?,is_location_manager = ?,location_manager_sitenumber = ?, can_view_tickets = ?, can_create_tickets = ?, can_edit_tickets = ?,can_delete_tickets = ?,supervisor_username = ? WHERE id = ?";
$stmt = mysqli_prepare($database, $query);
mysqli_stmt_bind_param($stmt, "ssssiiiiiiiiisi", $firstname, $lastname, $email, $ifasid, $is_admin, $is_tech, $is_supervisor, $is_loc_man, $man_location, $can_view_tickets, $can_create_tickets, $can_edit_tickets, $can_delete_tickets, $supervisor_username, $user_id);
mysqli_stmt_execute($stmt);

// Check if the query was successful
if (!$stmt) {
    die("Query failed: " . mysqli_error($database));
}

// Close the database connection
mysqli_close($database);

// Redirect back to the manage user page
$_SESSION['user_updated'] = 'User updated successfully';
log_app(LOG_INFO, "User '$modified_by' updated user '$username' information");
header("Location: manage_user.php?id=$user_id");
exit();
