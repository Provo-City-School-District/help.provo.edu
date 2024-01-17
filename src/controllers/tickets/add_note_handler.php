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

// Prepare the SQL statement to check for alerts on this ticket
$alert_stmt = mysqli_prepare($database, "SELECT * FROM alerts WHERE message = ? AND ticket_id = ?");

// Bind the parameters
$message = "Ticket hasn't been updated in 48 hours";
mysqli_stmt_bind_param($alert_stmt, "si", $message, $ticket_id);

// Execute the statement
mysqli_stmt_execute($alert_stmt);

// Get the result
$result = mysqli_stmt_get_result($alert_stmt);

// Check if the alert exists
if (mysqli_num_rows($result) > 0) {
    // The alert exists, delete it
    $delete_stmt = mysqli_prepare($database, "DELETE FROM alerts WHERE message = ? AND ticket_id = ?");
    mysqli_stmt_bind_param($delete_stmt, "si", $message, $ticket_id);
    mysqli_stmt_execute($delete_stmt);
    mysqli_stmt_close($delete_stmt);
}

// Don't forget to close the statement
mysqli_stmt_close($alert_stmt);


// Redirect back to the edit ticket page
header("Location: edit_ticket.php?id=$ticket_id");
exit();
