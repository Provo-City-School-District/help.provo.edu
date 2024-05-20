<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

// Get the note ID and ticket ID from the query string
$ticket_id = filter_input(INPUT_POST, 'ticket_id', FILTER_SANITIZE_NUMBER_INT);
$note_id = filter_input(INPUT_POST, 'note_id', FILTER_SANITIZE_NUMBER_INT);

$active_user = $_SESSION['username'];

// Prepare a SQL statement to get the ticket
$notestmt = HelpDB::get()->prepare("SELECT * FROM notes WHERE note_id = ?");
// Bind the ticket_id to the SQL statement
$notestmt->bind_param("i", $note_id);
// Execute the SQL statement
$notestmt->execute();
// Get the result of the SQL statement
$noteresult = $notestmt->get_result();

// Execute the SQL statement and check if it failed
if (!$notestmt->execute()) {
    // Output the error message and exit
    die("Error executing SQL statement: " . HelpDB::get()->error);
}

// Fetch the ticket from the result
$note = $noteresult->fetch_assoc();
$notestmt->close();

// Check if the note belongs to the current user
if ($note['creator'] !== $active_user) {
    $_SESSION['current_status'] = 'You can only delete your own notes';
    $_SESSION['status_type'] = "error";
    // Redirect to the edit ticket page if the note does not belong to the current user
    header("Location: edit_ticket.php?id=$ticket_id");
    exit();
}


//create log entry of note information prior to deletion
$field_name = 'notedeleted';
$new_value = 'NA';
$old_value = $note['note'];
logTicketChange(HelpDB::get(), $ticket_id, $active_user, $field_name, $old_value, $new_value);


// // Check if the note_id is set
if (isset($note_id)) {
    // Prepare a SQL statement to delete the note
    $del_stmt = HelpDB::get()->prepare("DELETE FROM notes WHERE note_id = ?");
    // Bind the note_id to the SQL statement
    $del_stmt->bind_param("i", $note_id);
    // Execute the SQL statement
    $del_stmt->execute();
    // Execute the SQL statement and check if it failed
    if (!$del_stmt->execute()) {
        // Output the error message and exit
        die("Error executing SQL statement: " . HelpDB::get()->error);
    }
    // Close the statement
    $del_stmt->close();
    $_SESSION['current_status'] = 'Note deleted successfully';
    $_SESSION['status_type'] = "success";
    // Redirect to the edit ticket page
    header("Location: edit_ticket.php?id=$ticket_id");
}
