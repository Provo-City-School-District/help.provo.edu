<?php
session_start();
$num_assigned_tickets = 0;
$num_flagged_tickets = 0;


$num_assigned_tickets_query = <<<STR
    SELECT 1
    FROM tickets
    WHERE status NOT IN ('Closed', 'Resolved')
    AND employee = ?
    ORDER BY id ASC
STR;

$num_flagged_tickets_query = <<<STR
SELECT 1 FROM tickets 
WHERE
    tickets.id in (
        SELECT flagged_tickets.ticket_id from flagged_tickets WHERE flagged_tickets.user_id in (
            SELECT users.id FROM users WHERE users.username = ?
        )
    )
STR;

$assigned_stmt = mysqli_prepare($database, $num_assigned_tickets_query);
mysqli_stmt_bind_param($assigned_stmt, "s", $username);
$assigned_stmt_succeeded = mysqli_stmt_execute($assigned_stmt);
$assigned_res = mysqli_stmt_get_result($assigned_stmt);

if ($assigned_stmt_succeeded)
    $num_assigned_tickets = mysqli_num_rows($assigned_res);


$flagged_stmt = mysqli_prepare($database, $num_flagged_tickets_query);
mysqli_stmt_bind_param($flagged_stmt, "s", $username);
$flagged_stmt_succeeded = mysqli_stmt_execute($flagged_stmt);
$flagged_res = mysqli_stmt_get_result($flagged_stmt);

if ($flagged_stmt_succeeded)
    $num_flagged_tickets = mysqli_num_rows($flagged_res);

mysqli_stmt_close($assigned_stmt);
mysqli_stmt_close($flagged_stmt);


$subord_query = "SELECT count(supervisor_username) as supervisor_username FROM users WHERE supervisor_username = ?";
$subord_stmt = $database->prepare($subord_query);
$subord_stmt->bind_param("s", $userId);
$subord_stmt->execute();
$subord_result = $subord_stmt->get_result();
$subord_row = $subord_result->fetch_assoc();
$subord_count = $subord_row['supervisor_username'];
$subord_stmt->close();