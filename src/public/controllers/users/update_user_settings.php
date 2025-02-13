<?php
require_once('init.php');
require_once('helpdbconnect.php');
// Check if the user ID is set
if (!isset($_POST['id'])) {
    die("User ID not set");
}
$user_id = $_POST['id'];
//catch changes from the users profile page that the user is allowed to change.
if ($_POST['referer'] == 'profile.php') {
    // Retrieve the color_scheme from the form submission
    $color_scheme = $_POST['color_scheme'] ?? 'system';
    $note_order = $_POST['note_order'] ?? 'desc';
    $hide_alerts = isset($_POST['hide_alerts']) ? 1 : 0;
    $ticket_limit = $_POST['ticket_limit'] ?? 10;
    $note_count = $_POST['note_count'] ?? 5;
    $show_alerts = isset($_POST['show_alerts']) ? 1 : 0;

    // update color scheme with the new one. takes effect immediately
    $_SESSION['color_scheme'] = $color_scheme;
    $_SESSION['note_order'] = $note_order;
    $_SESSION['hide_alerts'] = $hide_alerts;
    $_SESSION['ticket_limit'] = $ticket_limit;
    $_SESSION['note_count'] = $note_count;


    // Update the color_scheme in the database
    $update_user_query = "UPDATE user_settings SET color_scheme = ?, note_count = ?, hide_alerts = ?, ticket_limit = ?, show_alerts = ? WHERE user_id = ?";
    $update_user_stmt = mysqli_prepare(HelpDB::get(), $update_user_query);
    mysqli_stmt_bind_param($update_user_stmt, "siiiii", $color_scheme, $note_count, $hide_alerts, $ticket_limit, $show_alerts, $user_id);

    $res = mysqli_stmt_execute($update_user_stmt);
    if ($res) {
        $_SESSION['current_status'] = "User updated successfully";
        $_SESSION['status_type'] = 'success';
    } else {
        $_SESSION['current_status'] = "Failed to update user: " . mysqli_error();
        $_SESSION['status_type'] = 'error';
    }
    header('Location: /profile.php');
    exit();
}
