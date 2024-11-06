<?php
require_once from_root('/../vendor/autoload.php');
require_once from_root("/new-controllers/base_variables.php");

session_start();

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache')
]);



$user_query = "SELECT * FROM users WHERE username = ?";
$user_result = HelpDB::get()->execute_query($user_query, [$username]);
// Check if the query was successful
if (!$user_result) {
    die("Query failed: " . mysqli_error($conn));
}
$user_data = mysqli_fetch_assoc($user_result);

$user_id = $user_data['id'];
$employee_id = $user_data['ifasid'];
$username = $user_data['username'];
$firstname = ucfirst(strtolower($user_data['firstname']));
$lastname = ucfirst(strtolower($user_data['lastname']));
$email = $user_data['email'];
$color_scheme = $user_data['color_scheme'];
// $note_order = $user_data['note_order'];
$hide_alerts = $user_data['hide_alerts'];
$note_count = $user_data['note_count'];

if ($permissions["is_tech"]) {
    require_once("time_utils.php");

    // Get day for M-F belonging to current work week
    $monday_timestamp = null;
    if (date('w') == 1)
        $monday_timestamp = strtotime("today");
    else
        $monday_timestamp = strtotime("last Monday");

    $tuesday_timestamp = strtotime('+1 day', $monday_timestamp);
    $wednesday_timestamp = strtotime('+2 day', $monday_timestamp);
    $thursday_timestamp = strtotime('+3 day', $monday_timestamp);
    $friday_timestamp = strtotime('+4 day', $monday_timestamp);

    $user_times = get_note_time_for_days($username, [$monday_timestamp, $tuesday_timestamp, $wednesday_timestamp, $thursday_timestamp, $friday_timestamp]);

    $user_time_total = 0;
    foreach ($user_times as $idx => $time) {
        $user_time_total += $user_times[$idx];
        $user_time_hour = $user_times[$idx] / 60;
        $user_times[$idx] = number_format($user_time_hour, 2);
    }

    $user_time_total /= 60;
    $user_time_total = number_format($user_time_total, 2);
}

echo $twig->render('profile.twig', [
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

    // profile variables
    'user_id' => $user_id,
    'employee_id' => $employee_id,
    'username' => $username,
    'first_name' => $firstname,
    'last_name' => $lastname,
    'email' => $email,
    // 'note_order' => $note_order,
    'hide_alerts' => $hide_alerts,
    'user_times' => $user_times,
    'user_time_total' => $user_time_total,
    'note_count' => $note_count,
]);
