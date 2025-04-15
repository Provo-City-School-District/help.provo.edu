<?php

require_once('helpdbconnect.php');
require_once('functions.php');
require_once('email_utils.php');

log_app(LOG_INFO, "close_resolved.php running");

// Prepare a SQL statement to select tickets that need to be closed
$select_tickets_query = "SELECT id, client, name, employee FROM help.tickets WHERE status = 'resolved' AND last_updated < NOW() - INTERVAL 10 DAY";
$select_tickets_result = HelpDB::get()->execute_query($select_tickets_query);


foreach ($select_tickets_result as $ticket) {
    $ticket_id = $ticket['id'];
    $ticket_subject = $ticket['name'];
    $client_email = email_address_from_username($ticket['client']);
    $client_name_array = get_local_name_for_user($ticket['client']);
    $client_name = $client_name_array['firstname'] . " " . $client_name_array['lastname'];
    $unique_id = bin2hex(random_bytes(64)); // Generate a unique ID

    // Update the ticket status to 'closed' and store the unique ID
    $update_query = "UPDATE help.tickets SET status = 'closed', feedback_id = ? WHERE id = ?";
    $update_stmt = HelpDB::get()->prepare($update_query);
    $update_stmt->execute([$unique_id, $ticket_id]);

    // Check that employee and client do not match before sending email
    if ($ticket['client'] !== $ticket['employee']) {
        // Send an email to the client with the feedback URL
        $feedback_url = getenv('ROOTDOMAIN') . "/feedback.php?id=$unique_id";
        $subject = "Ticket $ticket_id - $ticket_subject has been Closed - We Would Value Your Feedback";
        $message = "Dear $client_name,<br><br>Your ticket with subject: $ticket_subject, and ID: $ticket_id has been closed. We would appreciate your feedback. Please click the link below to provide your feedback:<br><br>$feedback_url<br><br>Thank you!";
        $headers = "From: no-reply@yourdomain.com";
        // Send the email
        send_email($client_email, $subject, $message);
        log_app(LOG_INFO, "close_resolved.php: feedback email sent to $client_email for ticket $ticket_id");
    }

    log_app(LOG_INFO, "close_resolved.php completed");
}
