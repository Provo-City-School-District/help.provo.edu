<?php
require_once('helpdbconnect.php');
require_once('ticket_utils.php');
require_once("email_utils.php");
require("template.php");

// Todays date
$today = new DateTime();
$template_path = "ticket_response_required";
try {
    $client_response_tickets_query = "SELECT * FROM tickets WHERE priority = 15";
    $client_response_tickets_results = $database->execute_query($client_response_tickets_query);
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




        // Array (
        //         [id] => 127425
        //         [created] => 2024-03-15 06:52:03
        //         [due_date] => 2024-04-10
        //         [last_updated] => 2024-03-15 13:51:48
        //         [name] => Door Sensor Monitors
        //         [description] => &lt;p&gt;We are going to switch email reports to weekly instead of daily&lt;/p&gt;
        //         [location] => 1896
        //         [room] => 
        //         [employee] => joshe
        //         [client] => CharityW
        //         [status] => open
        //         [attachment_path] => 
        //         [phone] => 
        //         [request_type_id] => 143
        //         [cc_emails] => chadd@provo.edu
        //         [bcc_emails] => 
        //         [priority] => 15
        //         [merged_into_id] => 
        //         [parent_ticket] => 0
        //         [email_msg_id] => 
        //     )


        // Build Email Template
        $template_client = new Template(__DIR__ . "/templates/{$template_path}_client.phtml");

        $client_name = get_local_name_for_user($ticket['client']);
        $template_client->client = $client_name["firstname"] . " " . $client_name["lastname"];
        $template_client->location = $ticket['location'];
        $template_client->ticket_id = $ticket['id'];
        $template_client->notes_message = $notesMessageClient;
        $template_client->site_url = getenv('ROOTDOMAIN');
        $template_client->description = html_entity_decode($ticket['description']);

        // print_r($template_client);
        $ticket['client'] = 'joshe@provo.edu';
        // omitting the email CC,BCC,Attachments on client reminder
        $ticket['cc_emails'] = array();
        $ticket['bcc_emails'] = array();
        $ticket['attachment_path'] = array();
        // log_app(LOG_INFO, "Sending email to Tickets with priority 15. Ticket ID: " . $ticket['id'] . " Title: " . $ticket['name']);
        send_email_and_add_to_ticket($ticket['id'], $ticket['client'], $ticket['name'], $template_client, $ticket['cc_emails'], $ticket['bcc_emails'], $ticket['attachment_path']);
        // echo $ticket['id'] . ", " . $ticket['name'] . "\n";
    }
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
