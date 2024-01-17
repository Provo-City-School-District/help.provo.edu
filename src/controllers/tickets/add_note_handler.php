<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');
require_once('ticket_utils.php');
// Return true on success, false otherwise


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


// Get visible to client state
$visible_to_client = false;
if (isset($_POST["visible_to_client"])) {
    $visible_to_client = true;
}


$add_note_result = add_note_with_filters(
    $ticket_id,
    $_POST['username'],
    $_POST['note'],
    $_POST['note_time'],
    $visible_to_client,
    $date_override
);

if ($add_note_result) {
    $_SESSION['current_status'] = "Note added";
    $_SESSION['status_type'] = "success";
} else {
    $_SESSION['current_status'] = "Failed to add note";
    $_SESSION['status_type'] = "error";
}

// Update the last_updated field on the tickets table
$update_stmt = mysqli_prepare($database, "UPDATE tickets SET last_updated = NOW() WHERE id = ?");
mysqli_stmt_bind_param($update_stmt, "i", $ticket_id);
mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

// Check if the ticket has an alert about not being updated in last 48 hours and clear it since the ticket was just updated.
$message = "Ticket hasn't been updated in 48 hours";
removeAlert($database, $message, $ticket_id);


// Redirect back to the edit ticket page
header("Location: edit_ticket.php?id=$ticket_id");
exit();
