<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');

/*
The host ticket is the one that will stay and will 'duplicate' from the source ticket.
The source ticket will be retained but will redirect to the new host ticket.
*/
function return_to_admin_with_status(string $status, string $status_type)
{
    $_SESSION['current_status'] = $status;
    $_SESSION['status_type'] = $status_type;
    header("Location: /admin.php");
    exit();    
}

$ticket_id_host = trim(htmlspecialchars($_POST["ticket_id_host"]));
$ticket_id_source = trim(htmlspecialchars($_POST["ticket_id_source"]));

if ($ticket_id_host == $ticket_id_source) {
    return_to_admin_with_status("tickets cannot be merged into themselves", "error");
}

$source_has_merged_query = "SELECT merged_into_id FROM tickets WHERE id = '$ticket_id_source'";
$source_has_merged_result = mysqli_query($database, $source_has_merged_query);

$source_merged = mysqli_fetch_assoc($source_has_merged_result);

if ($source_merged["merged_into_id"] != null) {
    $str = "Ticket ".$ticket_id_source." has already been merged into ticket ".$source_merged["merged_into_id"]." and cannot be merged again";
    return_to_admin_with_status($str, "error");
}

// disallow merging a ticket into the ticket that it merged into (loop)

$host_has_merged_query = "SELECT merged_into_id FROM tickets WHERE id = '$ticket_id_host'";
$host_has_merged_result = mysqli_query($database, $host_has_merged_query);

$host_merged = mysqli_fetch_assoc($host_has_merged_result);

if ($host_merged["merged_into_id"] == $ticket_id_source) {
    $str = "Ticket ".$ticket_id_host." has already merged into ".$host_merged["merged_into_id"].". This would cause a loop!";
    return_to_admin_with_status($str, "error");
}

$username = trim(htmlspecialchars($_POST['username']));

// Deep copy all source ticket's notes
$query = <<<STR
    INSERT INTO notes (linked_id, created, creator, note, time, idx, visible_to_client, date_override)
        SELECT '$ticket_id_host', created, creator, note, time, idx, visible_to_client, date_override FROM notes 
            WHERE linked_id = '$ticket_id_source'
STR;

$result = mysqli_query($database, $query);
if (!$result) {
    return_to_admin_with_status("failed to update notes", "error");
}

// Log the creation of merged ticket in the ticket_logs table for the host ticket
$log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at) 
    VALUES (?, ?, ?, ?, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))";
$log_stmt = mysqli_prepare($database, $log_query);

$field_name = "Ticket merged ";
mysqli_stmt_bind_param($log_stmt, "issii", $ticket_id_host, $username, $field_name, $ticket_id_source, $ticket_id_host);
$result = mysqli_stmt_execute($log_stmt);
if (!$result) {
    return_to_admin_with_status("failed to update host ticket merge status", "error");
}


// Log the creation of merged ticket in the ticket_logs table for the source ticket
$log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at) 
    VALUES (?, ?, ?, ?, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))";
$log_stmt = mysqli_prepare($database, $log_query);

$field_name = "Ticket merged ";
mysqli_stmt_bind_param($log_stmt, "issii", $ticket_id_source, $username, $field_name, $ticket_id_source, $ticket_id_host);
$result = mysqli_stmt_execute($log_stmt);
if (!$result) {
    return_to_admin_with_status("failed to update source ticket merge status", "error");
}

// Point old ticket towards new one
$complete_merge_query = "UPDATE tickets SET merged_into_id = ? WHERE id = ?";
$complete_merge_stmt = mysqli_prepare($database, $complete_merge_query);
mysqli_stmt_bind_param($complete_merge_stmt, "ii", $ticket_id_host, $ticket_id_source);
$result = mysqli_stmt_execute($complete_merge_stmt);
if (!$result) {
    return_to_admin_with_status("failed to update merge status on source ticket", "error");
}

return_to_admin_with_status("Tickets merged successfully", "success");