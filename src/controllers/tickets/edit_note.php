<?php
require_once('../../includes/init.php');
require_once('../../includes/helpdbconnect.php');

// Get the note ID and ticket ID from the query string
$note_id = trim(htmlspecialchars($_GET['note_id']));
$ticket_id = trim(htmlspecialchars($_GET['ticket_id']));

// Fetch the note from the database
$query = "SELECT * FROM notes WHERE note_id = ?";
$stmt = mysqli_prepare($database, $query);
mysqli_stmt_bind_param($stmt, "i", $note_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$note = mysqli_fetch_assoc($result);

// Check if the note belongs to the current user
if ($note['creator'] !== $_SESSION['username']) {
    // Redirect to the edit ticket page if the note does not belong to the current user

    //need to make a message that "cant edit others' notes"
    header("Location: edit_ticket.php?id=$ticket_id");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the updated note and time from the form data
    $updated_note = trim(htmlspecialchars($_POST['note']));
    $updated_time = trim(htmlspecialchars($_POST['note_time']));
    $timestamp = date('Y-m-d H:i:s');

    // Update the note in the database
    $query = "UPDATE notes SET note = ?, time = ? WHERE note_id = ?";
    $stmt = mysqli_prepare($database, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $updated_note, $updated_time, $note_id);
    mysqli_stmt_execute($stmt);

    // Log the note update in the ticket_logs table
    $noteColumn = "note";
    $log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at) VALUES (?, ?, ?, ?, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))";
    $log_stmt = mysqli_prepare($database, $log_query);
    mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $_SESSION['username'],  $noteColumn, $note['note'], $updated_note);
    mysqli_stmt_execute($log_stmt);

    // Redirect back to the edit ticket page
    header("Location: edit_ticket.php?id=$ticket_id");
    exit();
}
?>

<h2>Edit Note</h2>
<form method="post">
    <input type="hidden" name="note_id" value="<?= $note_id ?>">
    <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
    <label for="note">Note:</label>
    <textarea id="note" name="note"><?= $note['note'] ?></textarea><br>

    <label for="note_time">Time in Minutes:</label>
    <input id="note_time" name="note_time" value="<?= $note['time'] ?>"><br>
    <input type="submit" value="Save Note">
</form>