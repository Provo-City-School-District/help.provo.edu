<?php
require_once('init.php');
require_once('helpdbconnect.php');
require_once('functions.php');

if ($_SESSION['permissions']['is_supervisor'] != 1) {
    echo 'You do not have permission to use this form.';
    exit;
}

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
$is_intern = isset($_POST['is_intern']) ? 1 : 0;
$intern_site = trim(htmlspecialchars($_POST['intern_site']));
$is_supervisor = isset($_POST['is_supervisor']) ? 1 : 0;
$can_view_tickets = isset($_POST['can_view_tickets']) ? 1 : 0;
$can_create_tickets = isset($_POST['can_create_tickets']) ? 1 : 0;
$can_edit_tickets = isset($_POST['can_edit_tickets']) ? 1 : 0;
$can_delete_tickets = isset($_POST['can_delete_tickets']) ? 1 : 0;
$is_loc_man = isset($_POST['is_loc_man']) ? 1 : 0;
$supervisor_username = trim(htmlspecialchars($_POST['supervisor']));
$man_location = trim(htmlspecialchars($_POST['man_location']));
$department = trim(htmlspecialchars($_POST['department']));
$can_see_all_techs = isset($_POST['can_see_all_techs']) ? 1 : 0;

// Update the user data in the users table
$user_query = "UPDATE users SET firstname = ?, lastname = ?, email = ?, ifasid = ? WHERE id = ?";
$user_stmt = mysqli_prepare(HelpDB::get(), $user_query);
mysqli_stmt_bind_param($user_stmt, "ssssi", $firstname, $lastname, $email, $ifasid, $user_id);
mysqli_stmt_execute($user_stmt);

// Check if the query was successful
if (!$user_stmt) {
    die("Query failed: " . mysqli_error(HelpDB::get()));
}

// Update the user settings in the user_settings table
$settings_query = "UPDATE user_settings SET is_admin = ?, is_tech = ?, is_intern = ?, intern_site = ?, is_supervisor = ?, is_location_manager = ?, location_manager_sitenumber = ?, can_view_tickets = ?, can_create_tickets = ?, can_edit_tickets = ?, supervisor_username = ?, department = ?, can_see_all_techs = ? WHERE user_id = ?";
$settings_stmt = mysqli_prepare(HelpDB::get(), $settings_query);
mysqli_stmt_bind_param($settings_stmt, "iiiiiiiiiisiii", $is_admin, $is_tech, $is_intern, $intern_site, $is_supervisor, $is_loc_man, $man_location, $can_view_tickets, $can_create_tickets, $can_edit_tickets, $supervisor_username, $department, $can_see_all_techs, $user_id);
mysqli_stmt_execute($settings_stmt);

// Check if the query was successful
if (!$settings_stmt) {
    die("Query failed: " . mysqli_error(HelpDB::get()));
}

// Redirect back to the manage user page
$_SESSION['user_updated'] = 'User updated successfully';
log_app(LOG_INFO, "User '$modified_by' updated user '$username' information");
header("Location: manage_user.php?id=$user_id");
exit();
