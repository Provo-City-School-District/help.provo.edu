<?php
include("includes/header.php");
include("includes/helpdbconnect.php");
require("includes/time_utils.php");
$user_query = "SELECT * FROM users WHERE username = '" . $_SESSION['username'] . "'";
$user_result = mysqli_query($database, $user_query);
// Check if the query was successful
if (!$user_result) {
    die("Query failed: " . mysqli_error($conn));
}
$user_data = mysqli_fetch_assoc($user_result);
//print_r($user_data);


$user = $_SESSION["username"];

// Get day for M-F belonging to current work week
$monday_timestamp = strtotime("last Monday");
$tuesday_timestamp = strtotime('+1 day', $monday_timestamp);
$wednesday_timestamp = strtotime('+2 day', $monday_timestamp);
$thursday_timestamp = strtotime('+3 day', $monday_timestamp);
$friday_timestamp = strtotime('+4 day', $monday_timestamp);

$user_times = [];
$user_times["monday"] = get_days_note_time($monday_timestamp, $user) / 60;
$user_times["tuesday"] = get_days_note_time($tuesday_timestamp, $user) / 60;
$user_times["wednesday"] = get_days_note_time($wednesday_timestamp, $user) / 60;
$user_times["thursday"] = get_days_note_time($thursday_timestamp, $user) / 60;
$user_times["friday"] = get_days_note_time($friday_timestamp, $user) / 60;

$user_times["total"] = $user_times["monday"] + $user_times["tuesday"] +
    $user_times["wednesday"] + $user_times["thursday"] + $user_times["friday"];
?>

<h2>Profile For <?= $user_data['firstname'] . ' ' . $user_data['lastname'] ?> (<span><?= $user_data['username'] ?></span>)</h2>
<br>
<table>
    <tr>
        <th>Monday</th>
        <th>Tuesday</th>
        <th>Wednesday</th>
        <th>Thursday</th>
        <th>Friday</th>
        <th>Total</th>
    </tr>
    <tr>
        <td><?= number_format($user_times["monday"], 2) ?> hrs</td>
        <td><?= number_format($user_times["tuesday"], 2) ?> hrs</td>
        <td><?= number_format($user_times["wednesday"], 2) ?> hrs</td>
        <td><?= number_format($user_times["thursday"], 2) ?> hrs</td>
        <td><?= number_format($user_times["friday"], 2) ?> hrs</td>
        <td><?= number_format($user_times["total"], 2) ?> hrs</td>
    </tr>
</table>

<?php include("includes/footer.php"); ?>