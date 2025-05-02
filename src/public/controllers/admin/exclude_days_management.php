<?php
require_once from_root('/../vendor/autoload.php');
require_once from_root("/new-controllers/base_variables.php");
require "ticket_utils.php";

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    echo 'You do not have permission to view this page.';
    exit;
}


$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);

// Check if an error message is set
if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}

// Fetch the exclude days from the database. only displaying current and future exclude days
$exclude_result = HelpDB::get()->execute_query("SELECT * FROM exclude_days WHERE exclude_day >= CURDATE() ORDER BY exclude_day");
$exclude_parsed = [];
while ($row = mysqli_fetch_assoc($exclude_result)) {
    $exclude_parsed[] = [
        'id' => $row['id'],
        'exclude_day' => date('Y-m-d', strtotime($row['exclude_day'])),
        'entered_at' => date('Y-m-d H:i:s', strtotime($row['entered_at'])),
        'entered_by' => $row['entered_by'],
    ];
}

echo $twig->render('exclude_days_management.twig', [
    // base variables
    'color_scheme' => $color_scheme,
    'current_year' => $current_year,
    'user_permissions' => $permissions,
    'wo_time' => $wo_time,
    'user_pref' => $user_pref,
    'ticket_limit' => $ticket_limit,
    'status_alert_type' => $status_alert_type,
    'status_alert_message' => $status_alert_message,
    'app_version' => $app_version,

    // Page Variables
    'exclude_days' => $exclude_parsed,
    'username' => $_SESSION['username'],
    // 'user_result' => $users,
    // 'user_id' => $user_id,
    // 'employee_id' => $employee_id,
    // 'username' => $username,
    // 'first_name' => $firstname,
    // 'last_name' => $lastname,
    // 'email' => $email,
    // // 'note_order' => $note_order,
    // 'hide_alerts' => $hide_alerts,
    // 'user_times' => $user_times,
    // 'user_time_total' => $user_time_total,
    // 'note_count' => $note_count,
    // 'show_alerts' => $show_alerts
]);
