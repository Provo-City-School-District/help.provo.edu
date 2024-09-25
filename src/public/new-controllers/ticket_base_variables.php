<?php
require_once from_root("/new-controllers/base_variables.php");
require_once "ticket_utils.php";

if (!session_id()) {
    session_start();
}

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

$num_assigned_tasks_query = "SELECT COUNT(*) FROM ticket_tasks WHERE (NOT completed AND assigned_tech = ?)";
$num_assigned_tasks_result = HelpDB::get()->execute_query($num_assigned_tasks_query, [$username]);

$num_assigned_tasks = $num_assigned_tasks_result->fetch_column(0);

$num_assigned_intern_tickets = 0;
if (session_is_intern()) {
    $num_assigned_intern_tickets_query = <<<QUERY
        SELECT COUNT(1) FROM tickets WHERE intern_visible = 1 AND location = ?;
    QUERY;

    $intern_site = $_SESSION["permissions"]["intern_site"];
    if (!isset($intern_site) || $intern_site == 0) {
        log_app(LOG_INFO, "[header.php] intern_site not set. failed to get ticket count");
    }
    $ticket_result = HelpDB::get()->execute_query($num_assigned_intern_tickets_query, [$intern_site]);
    $ticket_result_data = mysqli_fetch_assoc($ticket_result);
    $num_assigned_intern_tickets = $ticket_result_data['COUNT(1)'];
}

$assigned_stmt = mysqli_prepare(HelpDB::get(), $num_assigned_tickets_query);
mysqli_stmt_bind_param($assigned_stmt, "s", $username);
$assigned_stmt_succeeded = mysqli_stmt_execute($assigned_stmt);
$assigned_res = mysqli_stmt_get_result($assigned_stmt);

if ($assigned_stmt_succeeded)
    $num_assigned_tickets = mysqli_num_rows($assigned_res);


$flagged_stmt = mysqli_prepare(HelpDB::get(), $num_flagged_tickets_query);
mysqli_stmt_bind_param($flagged_stmt, "s", $username);
$flagged_stmt_succeeded = mysqli_stmt_execute($flagged_stmt);
$flagged_res = mysqli_stmt_get_result($flagged_stmt);

if ($flagged_stmt_succeeded)
    $num_flagged_tickets = mysqli_num_rows($flagged_res);

mysqli_stmt_close($assigned_stmt);
mysqli_stmt_close($flagged_stmt);


$subord_query = "SELECT count(supervisor_username) as supervisor_username FROM users WHERE supervisor_username = ?";
$subord_stmt = HelpDB::get()->prepare($subord_query);
$subord_stmt->bind_param("s", $username);
$subord_stmt->execute();
$subord_result = $subord_stmt->get_result();
$subord_row = $subord_result->fetch_assoc();
$subord_count = $subord_row['supervisor_username'];
$subord_stmt->close();