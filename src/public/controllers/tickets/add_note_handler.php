<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');
require_once('ticket_utils.php');


$ticket_id = $_POST['ticket_id'];

$date_override = null;
if (isset($_POST["date_override_enable"])) {
    // validate it can be created into a date
    $date_override_timestamp = strtotime($_POST["date_override"]);
    $date_override = date('Y-m-d H:i:s', $date_override_timestamp);
    if (!$date_override || !$date_override_timestamp) {
        $error = "Date override was invalid";
        $_SESSION['current_status'] = $error;
        $_SESSION['status_type'] = "error";
        $formData = http_build_query($_POST);
        header("Location: edit_ticket.php?id=$ticket_id&$formData");
        exit;
    }
}

// sanitize the input
$ticket_id = filter_var($ticket_id, FILTER_SANITIZE_NUMBER_INT);
$work_hours = filter_var($_POST['work_hours'], FILTER_SANITIZE_NUMBER_INT);
$work_minutes = filter_var($_POST['work_minutes'], FILTER_SANITIZE_NUMBER_INT);
$travel_hours = filter_var($_POST['travel_hours'], FILTER_SANITIZE_NUMBER_INT);
$travel_minutes = filter_var($_POST['travel_minutes'], FILTER_SANITIZE_NUMBER_INT);
$note_content = trim($_POST['note']);
$username = $_SESSION["username"];

// Get visible to client state
$visible_to_client = false;
if (isset($_POST["visible_to_client"])) {
    $visible_to_client = true;
}

// // Super crusty, open to other ideas, but this seemed to work for now
// // Check if the note content matches the last note content stored in the session for the same ticket
// if (isset($_SESSION['last_note']) && $_SESSION['last_note']['ticket_id'] === $ticket_id && $_SESSION['last_note']['content'] === $note_content) {
//     log_app(LOG_INFO, "Duplicate note detected");
//     $_SESSION['current_status'] = "Duplicate note detected";
//     $_SESSION['status_type'] = "error";
// } else {
// Add the note if it doesn't match the last note content for the same ticket
$add_note_result = create_note(
    $ticket_id,
    $username,
    $note_content,
    $work_hours,
    $work_minutes,
    $travel_hours,
    $travel_minutes,
    $visible_to_client,
    $date_override
);

if ($add_note_result) {
    $_SESSION['current_status'] = "Note added";
    $_SESSION['status_type'] = "success";
    // Store the note content and ticket_id in the session
    $_SESSION['last_note'] = [
        'ticket_id' => $ticket_id,
        'content' => $note_content
    ];
} else {
    $_SESSION['current_status'] = "Failed to add note";
    $_SESSION['status_type'] = "error";
}

// Update the last_updated field on the tickets table
$update_stmt = mysqli_prepare(HelpDB::get(), "UPDATE tickets SET last_updated = NOW() WHERE id = ?");
mysqli_stmt_bind_param($update_stmt, "i", $ticket_id);
mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

// Check if the ticket has an alert about not being updated in last 48 hours and clear it since the ticket was just updated.
removeAlert(HelpDB::get(), $alert48Message, $ticket_id);
removeAlert(HelpDB::get(), $alert7DayMessage, $ticket_id);
// }

// Redirect back to the edit ticket page
header("Location: edit_ticket.php?id=$ticket_id");
exit();
