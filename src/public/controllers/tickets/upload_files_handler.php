<?php
require_once("block_file.php");
// Start the session
require_once('init.php');

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // User is not logged in, redirect to login page
    header('Location: /index.php');
    exit;
}

// Check if the ticket ID is set
if (!isset($_POST['ticket_id'])) {
    // Ticket ID is not set, redirect to tickets page
    header('Location: tickets.php');
    exit;
}

// include resources
require_once('helpdbconnect.php');
require_once('ticket_utils.php');
// File Upload Functions
require_once('file_upload_utils.php');


// Get the ticket ID and username from the POST data
$ticket_id = $_POST['ticket_id'];
$username = $_POST['username'];

// init arrays
$failed_files = [];
$uploaded_files = [];

// if has attachments, handle them
if (isset($_FILES['attachment'])) {
    list($failed_files, $uploaded_files) = handleFileUploads($_FILES, $ticket_id);
}

// Log the ticket changes
foreach ($uploaded_files as $fileName) {
    $field_name = 'Attachment';
    $oldValue = 'NA';
    logTicketChange(HelpDB::get(), $ticket_id, $username, $field_name, $oldValue, $fileName);
}
// check for errors
$failed_files_count = count($failed_files);
if ($failed_files_count != 0) {
    $error_str = 'Failed to upload file(s): ';

    for ($i = 0; $i < $failed_files_count; $i++) {
        $failed_file = $failed_files[$i];
        $filename = $failed_file["filename"];
        $fail_reason = $failed_file["fail_reason"];

        if ($i == $failed_files_count - 1)
            $error_str .= "$filename (Reason: $fail_reason)";
        else
            $error_str .= "$filename (Reason: $fail_reason), ";
    }

    $_SESSION['current_status'] = $error_str;
    $_SESSION['status_type'] = 'error';
} else {
    $_SESSION['current_status'] = "File(s) successfully uploaded";
    $_SESSION['status_type'] = 'success';
}

// Redirect back to the ticket
header("Location: edit_ticket.php?id=$ticket_id");
exit;
