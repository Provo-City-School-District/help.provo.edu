<?php
require_once 'block_file.php';
require_once 'time_utils.php';
session_regenerate_id(true);
if (!session_id()) {
    session_start();
}
//included in twig base_variables and in functions.php while transitioning to twig
$app_version = "1.4.9-1";

// check if logged in. redirects to login page if not
if (!$_SESSION['username']) {
    // Store the requested page in the session
    $_SESSION['requested_page'] = $_SERVER['REQUEST_URI'];

    header('Location:' . getenv('ROOTDOMAIN'));
    exit;
}

$username = $_SESSION["username"];

$permissions = [
    "is_supervisor" => $_SESSION["permissions"]["is_supervisor"] != 0,
    "is_admin" => $_SESSION["permissions"]["is_admin"] != 0,
    "is_tech" => $_SESSION["permissions"]["is_tech"] != 0,
    "is_intern" => $_SESSION["permissions"]["is_intern"] != 0,
    "is_location_manager" => $_SESSION["permissions"]["is_location_manager"] != 0,
];


$work_order_day_time = null;

$color_scheme = $_SESSION['color_scheme'];
$user_pref = isset($_SESSION['color_scheme']) ? $_SESSION['color_scheme'] : 'light';
$ticket_limit = isset($_SESSION['ticket_limit']) ? $_SESSION['ticket_limit'] : 10;

$day_timestamp = strtotime("today");
$day_ticket_times = get_note_time_for_days($username, [$day_timestamp]);

$day_time_min = $day_ticket_times[0] / 60;
$wo_time = number_format($day_time_min, 2);
$current_year = date("Y");

$status_alert_type = isset($_SESSION["status_type"]) ? $_SESSION["status_type"] : null;
$status_alert_message = isset($_SESSION["current_status"]) ? $_SESSION["current_status"] : null;

unset($_SESSION["status_type"]);
unset($_SESSION["current_status"]);
