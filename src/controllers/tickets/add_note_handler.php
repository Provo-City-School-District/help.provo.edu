<?php
require_once('../../includes/helpdbconnect.php');

// Get the ticket ID and note from the form data
$ticket_id = trim(htmlspecialchars($_POST['ticket_id']));
$note = trim(htmlspecialchars($_POST['note']));
$note_time = trim(htmlspecialchars($_POST['note_time']));
$username = trim(htmlspecialchars($_POST['username']));
$timestamp = date('Y-m-d H:i:s');

// Get visible to client state
$visible_to_client = 0;
if (isset($_POST["visible_to_client"])) {
    $visible_to_client = 1;
}

// Insert the new note into the database
$query = "INSERT INTO notes (linked_id, created, creator, note, time, visible_to_client) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($database, $query);
mysqli_stmt_bind_param($stmt, "issssi", $ticket_id, $timestamp, $username, $note, $note_time, $visible_to_client);
mysqli_stmt_execute($stmt);

// Log the creation of the new note in the ticket_logs table
$log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at) VALUES (?, ?, ?, NULL, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))";
$log_stmt = mysqli_prepare($database, $log_query);

$notecolumn = "note";
mysqli_stmt_bind_param($log_stmt, "isss", $ticket_id, $username, $notecolumn, $note);
mysqli_stmt_execute($log_stmt);

// Redirect back to the edit ticket page
header("Location: edit_ticket.php?id=$ticket_id");
exit();
