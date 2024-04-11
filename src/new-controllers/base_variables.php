<?php
require_once from_root('/includes/time_utils.php');
session_start();

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