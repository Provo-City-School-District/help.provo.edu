<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

/*
The host ticket is the one that will stay and will 'duplicate' from the source ticket.
The source ticket will be retained but will redirect to the new host ticket.
*/
function return_to_ticket_with_status(string $status, string $status_type, int $ticket)
{
    $_SESSION['current_status'] = $status;
    $_SESSION['status_type'] = $status_type;

    if ($status_type == "success")
        header("Location: /controllers/tickets/edit_ticket.php?id=$ticket");
    else
        header("Location: /controllers/tickets/edit_ticket.php?id=$ticket&nr=1");

    exit();
}

$ticket_id_host = trim(htmlspecialchars($_POST["ticket_id_host"]));
$ticket_id_source = trim(htmlspecialchars($_POST["ticket_id_source"]));

if (!(intval($ticket_id_host) > 0 && intval($ticket_id_source) > 0)) {
    return_to_ticket_with_status("The destination ticket is invalid", "error", $ticket_id_source);
}

if ($ticket_id_host == $ticket_id_source) {
    return_to_ticket_with_status("Tickets cannot be merged into themselves", "error", $ticket_id_source);
}

$source_has_merged_query = "SELECT merged_into_id FROM tickets WHERE id = '$ticket_id_source'";
$source_has_merged_result = mysqli_query($database, $source_has_merged_query);

$source_merged = mysqli_fetch_assoc($source_has_merged_result);

if ($source_merged["merged_into_id"] != null) {
    $str = "Ticket " . $ticket_id_source . " has already been merged into ticket " . $source_merged["merged_into_id"] . " and cannot be merged again";
    return_to_ticket_with_status($str, "error", $ticket_id_source);
}

// disallow merging a ticket into a ticket that the other ticket merged into (loop)

$host_has_merged_query = "SELECT merged_into_id FROM tickets WHERE id = '$ticket_id_host'";
$host_has_merged_result = mysqli_query($database, $host_has_merged_query);
$host_merged = mysqli_fetch_assoc($host_has_merged_result);

if ($host_merged["merged_into_id"] != null) {
    $str = "Ticket " . $ticket_id_host . " has already merged into " . $host_merged["merged_into_id"] . ".";
    return_to_ticket_with_status($str, "error", $ticket_id_source);
}


$username = trim(htmlspecialchars($_POST['username']));

// Deep copy all source ticket's notes
$query = <<<STR
    INSERT INTO notes (linked_id, created, creator, note, time, idx, visible_to_client, date_override, email_msg_id, work_hours, work_minutes, travel_hours, travel_minutes)
        (SELECT '$ticket_id_host', created, creator, note, time, idx, visible_to_client, date_override, email_msg_id, work_hours, work_minutes, travel_hours, travel_minutes FROM notes 
            WHERE linked_id = '$ticket_id_source')
STR;

$result = mysqli_query($database, $query);
if (!$result) {
    return_to_ticket_with_status("failed to update notes", "error", $ticket_id_source);
}

// Log the creation of merged ticket in the ticket_logs table for the host ticket
$field_name = "Ticket merged ";
logTicketChange($database, $ticket_id_host, $username, $field_name, $ticket_id_source, $ticket_id_host);

if (!$result) {
    return_to_ticket_with_status("failed to update host ticket merge status", "error", $ticket_id_source);
}


// Log the creation of merged ticket in the ticket_logs table for the source ticket
$field_name = "Ticket merged ";
logTicketChange($database, $ticket_id_source, $username, $field_name, $ticket_id_source, $ticket_id_host);

if (!$result) {
    return_to_ticket_with_status("failed to update source ticket merge status", "error", $ticket_id_source);
}

// Point old ticket towards new one
$complete_merge_query = "UPDATE tickets SET merged_into_id = ?, status = 'closed' WHERE id = ?";
$complete_merge_stmt = mysqli_prepare($database, $complete_merge_query);
mysqli_stmt_bind_param($complete_merge_stmt, "ii", $ticket_id_host, $ticket_id_source);
$result = mysqli_stmt_execute($complete_merge_stmt);
if (!$result) {
    return_to_ticket_with_status("failed to update merge status on source ticket", "error", $ticket_id_source);
}

// Copy attachments from old ticket to new one

$attachment_source_query = "SELECT attachment_path FROM tickets WHERE id = ?";
$attachment_source_stmt = mysqli_prepare($database, $attachment_source_query);
mysqli_stmt_bind_param($attachment_source_stmt, "i", $ticket_id_source);
$result = mysqli_stmt_execute($attachment_source_stmt);
if (!$result) {
    log_app(LOG_ERR, "Failed to copy attachment paths from source ticket $ticket_id_source. Ignoring...");
}
$data_source = mysqli_fetch_assoc(mysqli_stmt_get_result($attachment_source_stmt));

$attachment_host_query = "SELECT attachment_path FROM tickets WHERE id = ?";
$attachment_host_stmt = mysqli_prepare($database, $attachment_host_query);
mysqli_stmt_bind_param($attachment_host_stmt, "i", $ticket_id_host);
$result = mysqli_stmt_execute($attachment_host_stmt);
if (!$result) {
    log_app(LOG_ERR, "Failed to copy attachment paths from source ticket $ticket_id_host. Ignoring...");
}
$data_host = mysqli_fetch_assoc(mysqli_stmt_get_result($attachment_host_stmt));

$attachment_path_source = $data_source["attachment_path"];
$attachment_path_host = $data_host["attachment_path"];

$attachment_path_total = explode(',', $attachment_path_source . $attachment_path_host);
$attachment_path_new = implode(',', $attachment_path_total);


$attachment_insert_query = "UPDATE tickets SET attachment_path = ? WHERE id = ?";
$attachment_insert_stmt = mysqli_prepare($database, $attachment_insert_query);
mysqli_stmt_bind_param($attachment_insert_stmt, "si", $attachment_path_new, $ticket_id_host);
$result = mysqli_stmt_execute($attachment_insert_stmt);
if (!$result) {
    log_app(LOG_ERR, "Failed to merged combined attachment paths into $ticket_id_host. Ignoring...");
}

$username = $_SESSION["username"];

// Create note on host ticket
add_note_with_filters(
    $ticket_id_host,
    "System",
    "This ticket ($ticket_id_host) was merged from WO#$ticket_id_source by $username",
    0,
    0,
    0,
    0,
    false
);

// Create note on source ticket
add_note_with_filters(
    $ticket_id_source,
    "System",
    "This ticket ($ticket_id_source) was merged into WO#$ticket_id_host by $username",
    0,
    0,
    0,
    0,
    false
);


return_to_ticket_with_status("Tickets merged successfully", "success", $ticket_id_host);
