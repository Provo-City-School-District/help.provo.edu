<?php
require_once('../../includes/helpdbconnect.php');

/*
The host ticket is the one that will stay and will 'duplicate' from the source ticket.
The source ticket will be retained but will redirect to the new host ticket.
*/

$ticket_id_host = trim(htmlspecialchars($_POST["ticket_id_host"]));
$ticket_id_source = trim(htmlspecialchars($_POST["ticket_id_source"]));

if ($ticket_id_host == $ticket_id_source) {
    echo "Tickets cannot be merged into themselves";
    die();
}

$has_merged_query = "SELECT merged_into_id FROM tickets WHERE id = $ticket_id_source;";
$has_merged_result = mysqli_query($database, $has_merged_query);

$merged = mysqli_fetch_assoc($has_merged_result);

if ($merged["merged_into_id"] != null) {
    echo "Ticket ".$ticket_id_source." has already been merged into ".$ticket_id_host;
    die();
}

$username = trim(htmlspecialchars($_POST['username']));

// Deep copy all source ticket's notes
$query = "INSERT INTO notes (linked_id, created, creator, note, time, idx, visible_to_client)
    SELECT $ticket_id_host, created, creator, note, time, idx, visible_to_client FROM notes WHERE linked_id = $ticket_id_source
";

$result = mysqli_query($database, $query);
if (!$result) {
    echo "Failed to update notes";
    die();
}

// Log the creation of merged ticket in the ticket_logs table for the host ticket
$log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at) 
    VALUES (?, ?, ?, ?, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))";
$log_stmt = mysqli_prepare($database, $log_query);

$field_name = "Ticket merged ";
mysqli_stmt_bind_param($log_stmt, "issii", $ticket_id_host, $username, $field_name, $ticket_id_source, $ticket_id_host);
$result = mysqli_stmt_execute($log_stmt);
if (!$result) {
    echo "Failed to update host note history";
    die();
}


// Log the creation of merged ticket in the ticket_logs table for the source ticket
$log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at) 
    VALUES (?, ?, ?, ?, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))";
$log_stmt = mysqli_prepare($database, $log_query);

$field_name = "Ticket merged ";
mysqli_stmt_bind_param($log_stmt, "issii", $ticket_id_source, $username, $field_name, $ticket_id_source, $ticket_id_host);
$result = mysqli_stmt_execute($log_stmt);
if (!$result) {
    echo "Failed to update source note history";
    die();
}

// Point old ticket towards new one
$complete_merge_query = "UPDATE tickets SET merged_into_id = ? WHERE id = ?";
$complete_merge_stmt = mysqli_prepare($database, $complete_merge_query);
mysqli_stmt_bind_param($complete_merge_stmt, "ii", $ticket_id_host, $ticket_id_source);
$result = mysqli_stmt_execute($complete_merge_stmt);
if (!$result) {
    echo "Failed to update merge status on source ticket";
    die();
}

header("Location: ../../admin.php");
exit();
