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

$user_times = get_note_time_for_days($user, [$monday_timestamp, $tuesday_timestamp, $wednesday_timestamp, $thursday_timestamp, $friday_timestamp]);

// Convert times to hours
$user_times[0] /= 60;
$user_times[1] /= 60;
$user_times[2] /= 60;
$user_times[3] /= 60;
$user_times[4] /= 60;

// Add up all the hours for the total
$user_times[5] = array_sum($user_times);
?>

<h2>Profile For <?= $user_data['firstname'] . ' ' . $user_data['lastname'] ?> (<span><?= $user_data['username'] ?></span>)</h2>
<br>
<table id="profile_time_table">
    <tr>
        <th>Monday</th>
        <th>Tuesday</th>
        <th>Wednesday</th>
        <th>Thursday</th>
        <th>Friday</th>
        <th>Total</th>
    </tr>
    <tr>
        <td data-cell="Monday"><?= number_format($user_times[0], 2) ?> hrs</td>
        <td data-cell="Tuesday"><?= number_format($user_times[1], 2) ?> hrs</td>
        <td data-cell="Wednesday"><?= number_format($user_times[2], 2) ?> hrs</td>
        <td data-cell="Thursday"><?= number_format($user_times[3], 2) ?> hrs</td>
        <td data-cell="Friday"><?= number_format($user_times[4], 2) ?> hrs</td>
        <td data-cell="Week Total"><?= number_format($user_times[5], 2) ?> hrs</td>
    </tr>
</table>

<?php include("includes/footer.php"); ?>