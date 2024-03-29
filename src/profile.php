<?php
include("header.php");
require_once("helpdbconnect.php");
require_once("status_popup.php");

$user = $_SESSION["username"];
$user_query = "SELECT * FROM users WHERE username = ?";
$user_result = $database->execute_query($user_query, [$user]);
// Check if the query was successful
if (!$user_result) {
    die("Query failed: " . mysqli_error($conn));
}
$user_data = mysqli_fetch_assoc($user_result);

$user_id = $user_data['id'];
$username = $user_data['username'];
$firstname = $user_data['firstname'];
$lastname = $user_data['lastname'];
$email = $user_data['email'];
$current_color_scheme = $user_data['color_scheme'];

if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}

if ($_SESSION['permissions']['is_tech'] == 1) {
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

    // Convert times to hours
    $user_times[0] /= 60;
    $user_times[1] /= 60;
    $user_times[2] /= 60;
    $user_times[3] /= 60;
    $user_times[4] /= 60;

    // Add up all the hours for the total
    $user_times[5] = array_sum($user_times);
}
?>
<h1>Profile For <?= ucfirst(strtolower($user_data['firstname'])) . ' ' . ucfirst(strtolower($user_data['lastname'])) ?> (<span><?= $user_data['username'] ?></span>)</h1>
<h2>My Information</h2>
<ul>
    <li>Name: <?= ucfirst(strtolower($user_data['firstname'])) . ' ' . ucfirst(strtolower($user_data['lastname'])) ?></li>
    <li>Email: <?= $email ?></li>
    <li>Employee ID: <?= $user_data['ifasid'] ?></li>
</ul>
<h2>My Settings</h2>
<form action="controllers/users/update_user_settings.php" method="post" class="singleColForm">
    <!-- Controller Variables -->
    <input type="hidden" name="id" value="<?= $user_id ?>">
    <input type="hidden" name="referer" value="profile.php">
    <!-- User Options -->
    <div>
        <label for="color_scheme">Color Scheme:</label>
        <select id="color_scheme" name="color_scheme">
            <option value="system" <?= $current_color_scheme == 'system' ? 'selected' : '' ?>>System Select</option>
            <option value="dark" <?= $current_color_scheme == 'dark' ? 'selected' : '' ?>>Dark Mode</option>
            <option value="light" <?= $current_color_scheme == 'light' ? 'selected' : '' ?>>Light Mode</option>
        </select>
    </div>
    <div>
        <label for="note_order">Ticket Note Order:</label>
        <select id="note_order" name="note_order">
            <option value="ASC" <?= $user_data['note_order'] == 'ASC' ? 'selected' : '' ?>>Ascending</option>
            <option value="DESC" <?= $user_data['note_order'] == 'DESC' ? 'selected' : '' ?>>Descending</option>
        </select>
    </div>
    <div>
        <label for="hide_alerts">Hide Alerts Banner on "My Tickets" Page:</label>
        <input type="checkbox" id="hide_alerts" name="hide_alerts" <?= $user_data['hide_alerts'] == 1 ? 'checked' : '' ?>>
    </div>
    <div>
        <label for="ticket_limit">Default Entries Per Page:</label>
        <select id="ticket_limit" name="ticket_limit">
            <option value="10" <?= $user_data['ticket_limit'] == 10 ? 'selected' : '' ?>>10</option>
            <option value="25" <?= $user_data['ticket_limit'] == 25 ? 'selected' : '' ?>>25</option>
            <option value="50" <?= $user_data['ticket_limit'] == 50 ? 'selected' : '' ?>>50</option>
            <option value="100" <?= $user_data['ticket_limit'] == 100 ? 'selected' : '' ?>>100</option>
        </select>
    </div>

    <input type="submit" value="Update">
</form>

<h2>Help / Documentation</h2>
<a href="/note_shortcuts.php">Note Shorthand</a>
<?php
if ($_SESSION['permissions']['is_tech'] == 1) {
?>
    <h2>Current Week Work Order Hours</h2>
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
<?php
}
?>
<?php include("footer.php"); ?>