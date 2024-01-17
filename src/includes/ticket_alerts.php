<?php
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

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

// Get the current time minus 48 hours
$timestampTwoDaysAgo = date('Y-m-d H:i:s', strtotime('-48 hours'));

// Convert the current time minus 48 hours to a DateTime object
$twoDaysAgo = new DateTime($timestampTwoDaysAgo);

// Prepare a SQL statement to select tickets
$selectTicketsQuery = "SELECT id, employee, priority, due_date,last_updated FROM tickets WHERE status NOT IN ('closed', 'resolved')";
$selectTicketsStmt = $database->prepare($selectTicketsQuery);
$selectTicketsStmt->execute();

// Bind result variables
$selectTicketsStmt->bind_result($ticketId, $ticketEmployee, $ticketPriority, $ticketDueDate, $ticketLastUpdated);

// Fetch all tickets and store them in an array
$oldTickets = [];
while ($selectTicketsStmt->fetch()) {
    if ($ticketEmployee !== 'unassigned' && !is_null($ticketEmployee)) {
        $oldTickets[] = ['id' => $ticketId, 'employee' => $ticketEmployee, 'priority' => $ticketPriority, 'due_date' => $ticketDueDate, 'last_updated' => $ticketLastUpdated];
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

    // If the last_updated time is longer than two days ago, insert an alert for 48 hours since last update
    if ($lastUpdated < $twoDaysAgo) {
        insertAlertIfNotExists($database, $oldTicket, $alert48Message, 'warn');
    }

    // Alert for past due
    $daysUntilDueDate = getDaysUntilDueDate($oldTicket['due_date']);
    // If the ticket's priority is greater than the number of days until its due date, insert a new alert
    if ($oldTicket['priority'] > $daysUntilDueDate) {
        insertAlertIfNotExists($database, $oldTicket, $pastDueMessage, 'crit');
    }
}

$database->close();
