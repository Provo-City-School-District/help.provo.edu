<?php

require_once('helpdbconnect.php');
require_once('functions.php');
require_once('ticket_utils.php');

log_app(LOG_INFO, "check_for_repeat_tickets.php running " . date('Y-m-d H:i:s'));

//init variables
$today = date('Y-m-d');

// Fetch repeatable ticket entries due today or earlier
// $query = "SELECT * FROM repeatable_ticket_templates WHERE next_run_date <= ? AND status = 'active'";
$query = "SELECT * FROM repeatable_ticket_templates WHERE next_run_date <= ?";
$stmt = HelpDB::get()->prepare($query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

while ($template = $result->fetch_assoc()) {
    $ticket_assignment = get_username_by_id($template['created_by']);
    // Calculate due date: today's date + priority value weekdays
    $priority = $template['priority'] ?? 10; // Default to 10 if not set
    // Calculate due date: today's date + $priority weekdays
    $weekdays = 0;
    $due = new DateTime($today);
    while ($weekdays < intval($priority)) {
        $due->modify('+1 day');
        if ($due->format('N') < 6) { // 1 (Mon) to 5 (Fri)
            $weekdays++;
        }
    }
    $due_date = $due->format('Y-m-d');
    // Insert a new ticket using template data
    $insert = HelpDB::get()->prepare("
        INSERT INTO tickets 
        (employee,department, room, location, cc_emails, client, phone, request_type_id, name, description, created, priority,status,due_date)
        VALUES (?,?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 10,'open',?)
    ");
    $insert->bind_param(
        "sssssssssss",
        $ticket_assignment,
        $template['department'],
        $template['room'],
        $template['location'],
        $template['cc'],
        $template['client'],
        $template['phone_number'],
        $template['request_type'],
        $template['title'],
        $template['description'],
        $due_date
    );
    $insert->execute();

    // Calculate the next run date
    $next_run = new DateTime($today);
    do {
        switch ($template['interval_type']) {
            case 'daily':
                $next_run->modify('+' . intval($template['interval_value']) . ' days');
                break;
            case 'weekly':
                $next_run->modify('+' . intval($template['interval_value']) . ' weeks');
                break;
            case 'monthly':
                $next_run->modify('+' . intval($template['interval_value']) . ' months');
                break;
        }
        // Keep looping if next_run is still <= today
    } while ($next_run->format('Y-m-d') <= $today);

    $new_next_run_date = $next_run->format('Y-m-d');

    // Update the template's next_run_date
    $update = HelpDB::get()->prepare("UPDATE repeatable_ticket_templates SET next_run_date = ? WHERE id = ?");
    $update->bind_param('si', $new_next_run_date, $template['id']);
    $update->execute();
}


log_app(LOG_INFO, "check_for_repeat_tickets.php Complete " . date('Y-m-d H:i:s'));
