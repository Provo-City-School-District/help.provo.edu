<?php
include_once('helpdbconnect.php');
include_once('ticket_utils.php');
include_once("email_utils.php");
include_once("template.php");

log_app(LOG_INFO, "alert_client_response.php running");

try {
    $client_response_tickets_query = "SELECT * FROM tickets WHERE priority = 15 AND status = 'open'";
    $client_response_tickets_results = HelpDB::get()->execute_query($client_response_tickets_query);
    $client_response_tickets = $client_response_tickets_results->fetch_all(MYSQLI_ASSOC);

    foreach ($client_response_tickets as $ticket) {
        // can be used in debugging log mode later
        // log_app(LOG_INFO, "Processing Tickets with priority 15 to Send Reminder Email. Ticket ID: " . $ticket['id'] . " Name: " . $ticket['name']);

        // Set Ticket Variables
        $ticket_assigned = email_address_from_username($ticket['employee']);
        $ticket_client = email_address_from_username($ticket['client']);
        $notesMessageTech = "";

        // Get Notes  - reused from update_ticket.php
        $notes = get_ticket_notes($ticket['id'], 3);
        $notesMessageClient = "";
        if (count($notes) > 0) {
            $notesMessageClient .= "<tr><th>Date</th><th>Creator</th><th>Note</th></tr>";
        }

        // Build Notes for Email - reused from update_ticket.php
        foreach ($notes as $note) {
            $dateOverride = $note['date_override'];
            $effectiveDate = $dateOverride;
            if ($dateOverride == null)
                $effectiveDate = $note['created'];

            $dateStr = date_format(date_create($effectiveDate), "F jS\, Y h:i:s A");
            $noteCreator = $note['creator'];
            $decodedNote = htmlspecialchars_decode($note['note']);

            $note_theme = "";
            if (!user_is_tech($noteCreator)) {
                $note_theme = "nonTech";
            } else if ($note['visible_to_client'] == 0) {
                $note_theme = "notClientVisible";
            } else {
                $note_theme = "clientVisible";
            }

            $notesMessageTech .= "<tr><td>$dateStr</td><td>$noteCreator</td><td><span class=\"$note_theme\">$decodedNote</span></td></tr>";
            if ($note['visible_to_client']) {
                $notesMessageClient .= "<tr><td>$dateStr</td><td>$noteCreator</td><td><span class=\"$note_theme\">$decodedNote</span></td></tr>";
            }
        }

        // Build Email Template
        $template_path = "ticket_response_required";
        if (!file_exists(__DIR__ . "/../includes/templates/{$template_path}_client.phtml")) {
            throw new Exception('Template File not found');
            log_app(LOG_ERR, "Template File not found when running alert_client_response.php");
        }
        $template_client = new Template(__DIR__ . "/../includes/templates/{$template_path}_client.phtml");

        $client_name = get_local_name_for_user($ticket['client']);
        $template_client->client = $client_name["firstname"] . " " . $client_name["lastname"];
        $template_client->location = $ticket['location'];
        $template_client->ticket_id = $ticket['id'];
        $template_client->notes_message = $notesMessageClient;
        $template_client->site_url = getenv('ROOTDOMAIN');
        $template_client->description = html_entity_decode($ticket['description']);

        // omitting the email CC,BCC,Attachments on client reminder
        $ticket['cc_emails'] = array();
        $ticket['bcc_emails'] = array();
        $ticket['attachment_path'] = array();

        $ticket_subject = "Response Required For: Ticket: " . $ticket['id'] . " - " . $ticket['name'];

        // Send Email
        // can be used in debugging log mode later
        log_app(LOG_INFO, "Sending client reminder email for Ticket ID: " . $ticket['id'] . " Title: " . $ticket['name']);
        send_email_and_add_to_ticket($ticket['id'], $ticket_client, $ticket_subject, $template_client, $ticket['cc_emails'], $ticket['bcc_emails'], $ticket['attachment_path']);
        logTicketChange(HelpDB::get(), $ticket['id'], "system", "sent_emails", "N/A", "Client Reminder Email Sent to " . $ticket['client'] . " ");
    }
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
