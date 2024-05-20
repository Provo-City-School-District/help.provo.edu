<?php
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

// Todays date
$today = new DateTime();

// insert an alert into the alerts table if it doesn't already exist
function insertAlertIfNotExists(HelpDB::get(), $ticket, $alertMessage, $alertLevel, $superVisorAlert)
{
    // Prepare a SQL statement to select alerts with the same ticket_id, employee, and message
    $selectAlertsQuery = "SELECT 1 FROM alerts WHERE ticket_id = ? AND employee = ? AND message = ?";
    $selectAlertsStmt = HelpDB::get()->prepare($selectAlertsQuery);
    $selectAlertsStmt->bind_param("iss", $ticket['id'], $ticket['employee'], $alertMessage);
    $selectAlertsStmt->execute();

    // Bind result variable
    $selectAlertsStmt->bind_result($alertExists);

    // If an alert with the same ticket_id, employee, and message doesn't exist, insert a new alert
    if (!$selectAlertsStmt->fetch()) {
        $selectAlertsStmt->close();

        $insertAlertQuery = "INSERT INTO alerts (ticket_id, employee, message, alert_level,supervisor_alert) VALUES (?, ?, ?, ?, ?)";
        $insertAlertStmt = HelpDB::get()->prepare($insertAlertQuery);
        $insertAlertStmt->bind_param("isssi", $ticket['id'], $ticket['employee'], $alertMessage, $alertLevel, $superVisorAlert);
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

$hoursBack48 = 48;
$hoursBack7Days = 168;
$hoursBack15Days = 360;
$hoursBack20Days = 480;


function calculate_hours_back($hoursBack)
{
    $date = new DateTime();

    while ($hoursBack > 0) {
        $date->modify('-1 hour');
        $dayOfWeek = $date->format('w');

        // If the day of the week is a weekend, subtract 24 hours and continue to the next iteration
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            $date->modify('-24 hour');
            continue;
        }

        $startDate = clone $date;
        $startDate->modify('-1 hour');
        $endDate = clone $date;

        // Format the dates as 'Y-m-d'
        $startDateFormatted = $startDate->format('Y-m-d');
        $endDateFormatted = $endDate->format('Y-m-d');

        // Check if the date is an excluded date
        if (hasExcludedDate($startDateFormatted, $endDateFormatted) != 0) {
            $date->modify('-24 hour');
            continue;
        }

        $hoursBack--;
    }
    return $date;
}


// Prepare a SQL statement to select tickets
$selectTicketsQuery = "SELECT id, employee, priority, due_date,last_updated,status,client FROM tickets WHERE status NOT IN ('closed', 'resolved')";
$selectTicketsStmt = HelpDB::get()->prepare($selectTicketsQuery);
$selectTicketsStmt->execute();

// Bind result variables
$selectTicketsStmt->bind_result($ticketId, $ticketEmployee, $ticketPriority, $ticketDueDate, $ticketLastUpdated, $ticketStatus, $ticketClient);

// Fetch all tickets and store them in an array
$oldTickets = [];
while ($selectTicketsStmt->fetch()) {
    if ($ticketEmployee !== 'unassigned' && !is_null($ticketEmployee)) {
        $oldTickets[] = ['id' => $ticketId, 'employee' => $ticketEmployee, 'priority' => $ticketPriority, 'due_date' => $ticketDueDate, 'last_updated' => $ticketLastUpdated, 'status' => $ticketStatus, 'client' => $ticketClient];
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
    $date48HoursBack = calculate_hours_back($hoursBack48);
    $date7DaysBack = calculate_hours_back($hoursBack7Days);
    $date15DaysBack = calculate_hours_back($hoursBack15Days);
    $date20DaysBack = calculate_hours_back($hoursBack20Days);

    // If the last_updated time is longer than 7 days ago, insert an alert for 7 days.
    if (
        $lastUpdated < $date7DaysBack &&
        ($oldTicket['status'] == 'vendor' ||
            $oldTicket['status'] == 'maintenance' ||
            $oldTicket['priority'] == 15 ||
            ($oldTicket['priority'] == 30 && $oldTicket['client'] != $oldTicket['employee']) ||
            $oldTicket['priority'] == 60)
    ) {
        insertAlertIfNotExists(HelpDB::get(), $oldTicket, $alert7DayMessage, 'warn', 0);
        //else if not updated in 48 hours
    } else {
        if (
            $lastUpdated < $date48HoursBack &&
            ($oldTicket['status'] != 'vendor' &&
                $oldTicket['status'] != 'maintenance' &&
                $oldTicket['priority'] != 15 &&
                ($oldTicket['priority'] != 30 && $oldTicket['client'] != $oldTicket['employee']) &&
                $oldTicket['priority'] != 60)
        ) {
            insertAlertIfNotExists(HelpDB::get(), $oldTicket, $alert48Message, 'warn', 0);
        }
    }
    if ($lastUpdated < $date15DaysBack && intval($oldTicket['priority']) != 60 && intval($oldTicket['priority']) != 30) {
        insertAlertIfNotExists(HelpDB::get(), $oldTicket, $alert15DayMessage, 'warn', 1);
    }
    if ($lastUpdated < $date20DaysBack && intval($oldTicket['priority']) != 60 && intval($oldTicket['priority']) != 30) {
        insertAlertIfNotExists(HelpDB::get(), $oldTicket, $alert20DayMessage, 'warn', 1);
    }


    //================================================================================================
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

        insertAlertIfNotExists(HelpDB::get(), $oldTicket, $pastDueMessage, 'crit', 0);
    }
}

HelpDB::get()->close();
