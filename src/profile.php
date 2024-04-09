<?php
require_once from_root('/vendor/autoload.php');
require_once from_root('/includes/time_utils.php');
require_once "status_popup.php";
session_start();

if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}

$loader = new \Twig\Loader\FilesystemLoader(from_root('/views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/twig-cache')
]);

$permissions = [
    "is_supervisor" => $_SESSION["permissions"]["is_supervisor"] != 0,
    "is_admin" => $_SESSION["permissions"]["is_admin"] != 0,
    "is_tech" => $_SESSION["permissions"]["is_tech"] != 0
];


$work_order_day_time = null;

$color_scheme = $_SESSION['color_scheme'];
$user_pref = isset($_SESSION['color_scheme']) ? $_SESSION['color_scheme'] : 'light';
$ticket_limit = isset($_SESSION['ticket_limit']) ? $_SESSION['ticket_limit'] : 10;

$day_timestamp = strtotime("today");
$day_ticket_times = get_note_time_for_days($_SESSION["username"], [$day_timestamp]);

$day_time_min = $day_ticket_times[0] / 60;
$wo_time = number_format($day_time_min, 2);


$user = $_SESSION["username"];
$user_query = "SELECT * FROM users WHERE username = ?";
$user_result = $database->execute_query($user_query, [$user]);
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
$note_order = $user_data['note_order'];
$hide_alerts = $user_data['hide_alerts'];
$ticket_limit = $user_data['ticket_limit'];

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

    $user_times = get_note_time_for_days($user, [$monday_timestamp, $tuesday_timestamp, $wednesday_timestamp, $thursday_timestamp, $friday_timestamp]);

    $user_time_total = 0;
    foreach ($user_times as $idx => $time) {
        $user_time_total += $user_times[$idx];
        $user_time_hour = $user_times[$idx] / 60;
        $user_times[$idx] = number_format($user_time_hour, 2);
    }

    $user_time_total /= 60;
    $user_time_total = number_format($user_time_total, 2);
}

echo $twig->render('profile.phtml', [
    'user_id' => $user_id,
    'color_scheme' => $color_scheme,
    'current_year' => date("Y"),
    'user_permissions' => $permissions,
    'wo_time' => $wo_time,
    'user_pref' => $user_pref,
    'ticket_limit' => $ticket_limit,
    'employee_id' => $employee_id,
    'username' => $username,
    'first_name' => $firstname,
    'last_name' => $lastname,
    'email' => $email,
    'note_order' => $note_order,
    'hide_alerts' => $hide_alerts,
    'user_times' => $user_times,
    'user_time_total' => $user_time_total,
    'ticket_limit' => $ticket_limit
]);