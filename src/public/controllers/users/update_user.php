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

function user_audit_log_add(int $changed_by_user_id, string $type, string $field, string $old_value, string $new_value)
{
    $query = HelpDB::get()->execute_query("INSERT INTO admin_logs VALUES (?, ?, ?, ?, ?)",
        [$changed_by_user_id, $type, $field, $old_value, $new_value]);
}

function log_changes_for_fields(array $fields, array $old_data, array $new_data)
{
    foreach ($fields as $field) {
        if ($old_data[$field] != $new_data[$field]) {
            user_audit_log_add($_SESSION["user_id"], "user", $field, $old_data[$field], $new_data[$field]);
        }
    }
}

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
$department = trim(htmlspecialchars($_POST['department'])) ?: null;
$can_see_all_techs = isset($_POST['can_see_all_techs']) ? 1 : 0;
$can_input_maintenance_tickets = isset($_POST['can_input_maintenance_tickets']) ? 1 : 0;
$is_developer = isset($_POST['is_developer']) ? 1 : 0;
$view_stats = isset($_POST['view_stats']) ? 1 : 0;

$old_user_result = HelpDB::get()->execute_query("SELECT * FROM users WHERE id = ?", [$user_id]);
$old_user_data = $old_user_result->fetch_assoc();

// Update the user data in the users table
$user_query = "UPDATE users SET firstname = ?, lastname = ?, email = ?, ifasid = ? WHERE id = ?";
$user_stmt = mysqli_prepare(HelpDB::get(), $user_query);
mysqli_stmt_bind_param($user_stmt, "ssssi", $firstname, $lastname, $email, $ifasid, $user_id);
mysqli_stmt_execute($user_stmt);

// Check if the query was successful
if (!$user_stmt) {
    die("Query failed: " . mysqli_error(HelpDB::get()));
}


$old_user_settings_result = HelpDB::get()->execute_query("SELECT * FROM user_settings WHERE user_id = ?", [$user_id]);
$old_user_settings = $old_user_settings_result->fetch_assoc();

// Update the user settings in the user_settings table
$settings_query = "UPDATE user_settings SET is_admin = ?, is_tech = ?, is_intern = ?, intern_site = ?, is_supervisor = ?, is_location_manager = ?, location_manager_sitenumber = ?, can_view_tickets = ?, can_create_tickets = ?, can_edit_tickets = ?, supervisor_username = ?, department = ?, can_see_all_techs = ?, can_input_maintenance_tickets = ?, is_developer = ?, view_stats = ? WHERE user_id = ?";
$settings_stmt = mysqli_prepare(HelpDB::get(), $settings_query);
mysqli_stmt_bind_param($settings_stmt, "iiiiiiiiiisiiiiii", $is_admin, $is_tech, $is_intern, $intern_site, $is_supervisor, $is_loc_man, $man_location, $can_view_tickets, $can_create_tickets, $can_edit_tickets, $supervisor_username, $department, $can_see_all_techs, $can_input_maintenance_tickets, $is_developer, $view_stats, $user_id);
mysqli_stmt_execute($settings_stmt);

// Check if the query was successful
if (!$settings_stmt) {
    die("Query failed: " . mysqli_error(HelpDB::get()));
}

$new_user_result = HelpDB::get()->execute_query("SELECT * FROM users WHERE id = ?", [$user_id]);
$new_user_data = $new_user_result->fetch_assoc();

$new_user_settings_result = HelpDB::get()->execute_query("SELECT * FROM user_settings WHERE user_id = ?", [$user_id]);
$new_user_settings = $new_user_settings_result->fetch_assoc();

// log changes to audit log
log_changes_for_fields(
    ['firstname', 'lastname', 'email', 'ifasid'],
    $old_user_data,
    $new_user_data
);

log_changes_for_fields(
    [
        'show_alerts',
        'can_view_tickets',
        'can_create_tickets',
        'can_edit_tickets',
        'is_admin',
        'is_tech',
        'is_supervisor',
        'is_intern',
        'intern_site',
        'supervisor_username',
        'is_location_manager',
        'location_manager_sitenumber',
        'color_scheme',
        'note_order',
        'hide_alerts',
        'ticket_limit',
        'note_count',
        'department',
        'can_see_all_techs',
        'can_input_maintenance_tickets',
        'is_developer',
        'view_stats'
    ],
    $old_user_settings,
    $new_user_settings
);

// Redirect back to the manage user page
$_SESSION['user_updated'] = 'User updated successfully';
log_app(LOG_INFO, "User '$modified_by' updated user '$username' information");
header("Location: manage_user.php?id=$user_id");
exit();
