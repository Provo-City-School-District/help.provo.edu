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
        if (!file_exists(__DIR__ . "/../public/includes/templates/{$template_path}_client.phtml")) {
            // TODO: write test for template file existence
            log_app(LOG_ERR, "Template File not found when running alert_client_response.php");
            throw new Exception('Template File not found');
        }
        $template_client = new Template(__DIR__ . "/../public/includes/templates/{$template_path}_client.phtml");

        $client_name = get_local_name_for_user($ticket['client']);
        $template_client->client = $client_name["firstname"] . " " . $client_name["lastname"];
        $template_client->location = $ticket['location'];
        $template_client->ticket_id = $ticket['id'];
        $template_client->notes_message = $notesMessageClient;
        $template_client->site_url = getenv('ROOTDOMAIN');
        $template_client->description = html_entity_decode($ticket['description']);

        // Parse cc_emails and bcc_emails into arrays if they are not empty
        $cc_emails = [];
        $bcc_emails = [];
        if ($ticket['send_cc_emails'] == 1) {
            $cc_emails = !empty($ticket['cc_emails']) ? explode(',', $ticket['cc_emails']) : [];
        }
        if ($ticket['send_bcc_emails'] == 1) {
            $bcc_emails = !empty($ticket['bcc_emails']) ? explode(',', $ticket['bcc_emails']) : [];
        }
        //omit attachment for now
        $ticket['attachment_path'] = array();

        $ticket_subject = "Response Required For: Ticket: " . $ticket['id'] . " - " . $ticket['name'];

        // Send Email
        log_app(LOG_INFO, "Sending client reminder email for Ticket ID: " . $ticket['id'] . " Title: " . $ticket['name']);
        send_email_and_add_to_ticket($ticket['id'], $ticket_client, $ticket_subject, $template_client, $cc_emails, $bcc_emails, $ticket['attachment_path']);

        // Concatenate CC and BCC emails into a string if they are not empty
        $cc_emails_str = !empty($cc_emails) ? " CC'd: " . implode(', ', $cc_emails) : "";
        $bcc_emails_str = !empty($bcc_emails) ? " BCC'd: " . implode(', ', $bcc_emails) : "";

        // Include CC and BCC emails in the log if they are not empty
        logTicketChange(
            HelpDB::get(),
            $ticket['id'],
            "system",
            "sent_emails",
            "N/A",
            "Client Reminder Email Sent to ticket Client " . $ticket['client'] . ". " . $cc_emails_str . $bcc_emails_str
        );
    }
} catch (Exception $e) {

    // Log the exception
    log_app(LOG_ERR, "Failed to send client reminder email for Ticket ID: " . $ticket['id'] . " Title: " . $ticket['name'] . ". Error: " . $e->getMessage());

    // Send an email to dev@provo.edu
    $admin_email = 'dev@provo.edu';
    $admin_subject = "Failed to send client reminder email for Ticket ID: " . $ticket['id'];
    $admin_message = "An error occurred while sending the client reminder email for Ticket ID: " . $ticket['id'] . ".\n\nError: " . $e->getMessage();
    send_email($admin_email, $admin_subject, $admin_message);


    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
