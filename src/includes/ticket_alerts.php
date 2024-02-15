<?php
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

// Todays date
$today = new DateTime();

// insert an alert into the alerts table if it doesn't already exist
function insertAlertIfNotExists($database, $ticket, $alertMessage, $alertLevel)
{
    // Prepare a SQL statement to select alerts with the same ticket_id, employee, and message
    $selectAlertsQuery = "SELECT 1 FROM alerts WHERE ticket_id = ? AND employee = ? AND message = ?";
    $selectAlertsStmt = $database->prepare($selectAlertsQuery);
    $selectAlertsStmt->bind_param("iss", $ticket['id'], $ticket['employee'], $alertMessage);
    $selectAlertsStmt->execute();

    // Bind result variable
    $selectAlertsStmt->bind_result($alertExists);

    // If an alert with the same ticket_id, employee, and message doesn't exist, insert a new alert
    if (!$selectAlertsStmt->fetch()) {
        $selectAlertsStmt->close();

        $insertAlertQuery = "INSERT INTO alerts (ticket_id, employee, message, alert_level) VALUES (?, ?, ?, ?)";
        $insertAlertStmt = $database->prepare($insertAlertQuery);
        $insertAlertStmt->bind_param("isss", $ticket['id'], $ticket['employee'], $alertMessage, $alertLevel);
        $insertAlertStmt->execute();
        $insertAlertStmt->close();
    } else {
        $selectAlertsStmt->close();
    }
}

// calculate the number of days till/past due
function getDaysUntilDueDate($dueDateStr)
{
    $dueDate = new DateTime($dueDateStr);
    $now = new DateTime();
    $interval = $now->diff($dueDate);
    $daysUntilDueDate = (int)$interval->format('%R%a');
    return $daysUntilDueDate;
}

// Calculate for 48 hours ago, not counting weekends hours
$twoDaysAgo = clone $today;
// Set counter for hours back
$hoursBack = 48;
// Parse 48 hours back since last update
while ($hoursBack > 0) {
    $twoDaysAgo->modify('-1 hour');
    $dayOfWeek = $twoDaysAgo->format('w');

    // If the day of the week is not a weekend, subtract an hour
    // 0 = Sunday, 6 = Saturday
    if ($dayOfWeek > 0 && $dayOfWeek < 6) {
        $hoursBack--;
    }
}




// Prepare a SQL statement to select tickets
$selectTicketsQuery = "SELECT id, employee, priority, due_date,last_updated,status FROM tickets WHERE status NOT IN ('closed', 'resolved')";
$selectTicketsStmt = $database->prepare($selectTicketsQuery);
$selectTicketsStmt->execute();

// Bind result variables
$selectTicketsStmt->bind_result($ticketId, $ticketEmployee, $ticketPriority, $ticketDueDate, $ticketLastUpdated, $ticketStatus);

// Fetch all tickets and store them in an array
$oldTickets = [];
while ($selectTicketsStmt->fetch()) {
    if ($ticketEmployee !== 'unassigned' && !is_null($ticketEmployee)) {
        $oldTickets[] = ['id' => $ticketId, 'employee' => $ticketEmployee, 'priority' => $ticketPriority, 'due_date' => $ticketDueDate, 'last_updated' => $ticketLastUpdated, 'status' => $ticketStatus];
    }
}
$selectTicketsStmt->close();

// Current Alerts available
// warn - Ticket hasn't been updated in 48 hours
// crit - Past Due

// For each ticket, insert an alert into the alerts table
foreach ($oldTickets as $oldTicket) {
    // Convert the last_updated time to a DateTime object
    $lastUpdated = new DateTime($oldTicket['last_updated']);
    $dueDate = $oldTicket['due_date'];

    if ($oldTicket['status'] == 'vendor' || $oldTicket['status'] == 'maintenance' || $oldTicket['status'] == 'pending' || $oldTicket['priority'] == 15 || $oldTicket['priority'] == 30 || $oldTicket['priority'] == 60) {
        // alert to be written I believe in the old system this status gets a 7 day alert

        // If the last_updated time is longer than two days ago, insert an alert for 48 hours since last update
    } elseif ($lastUpdated < $twoDaysAgo) {
        insertAlertIfNotExists($database, $oldTicket, $alert48Message, 'warn');
    }

    // Alert for past due
    $daysUntilDueDate = getDaysUntilDueDate($dueDate);
    $todaystr = $today->format('Y-m-d');
    $withexcludeDays = hasExcludedDate($dueDate, $todaystr);
    $modifiedDueDate = new DateTime($dueDate);

    // check that date is not a weekend
    while (isWeekend($modifiedDueDate)) {
        $modifiedDueDate->modify('+1 day');
    }

    $daysTillDue = $today->diff($modifiedDueDate);

    // If the ticket's priority is greater than the number of days until its due date, insert a new alert
    if ($daysTillDue->format('%R%a') < 0) {

        insertAlertIfNotExists($database, $oldTicket, $pastDueMessage, 'crit');
    }
}

$database->close();
