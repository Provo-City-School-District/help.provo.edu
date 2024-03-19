<?php
require_once('helpdbconnect.php');
require_once('ticket_utils.php');
require_once("email_utils.php");
require("template.php");

// Todays date
$today = new DateTime();

try {
    $client_response_tickets_query = "SELECT * FROM tickets WHERE priority = 15";
    $client_response_tickets_results = $database->execute_query($client_response_tickets_query);
    $client_response_tickets = $client_response_tickets_results->fetch_all(MYSQLI_ASSOC);

    foreach ($client_response_tickets as $ticket) {
        // Get Notes  - reused from update_ticket.php
        $notes = get_ticket_notes($ticket['id'], 3);
        $notesMessageClient = "";
        if (count($notes) > 0) {
            $notesMessageClient .= "<tr><th>Date</th><th>Creator</th><th>Note</th></tr>";
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
        $template_client = new Template(__DIR__ . "/templates/{$template_path}_client.phtml");

        $client_name = get_local_name_for_user($ticket['client']);
        $template_client->client = $client_name["firstname"] . " " . $client_name["lastname"];
        $template_client->location = $ticket['location'];
        $template_client->ticket_id = $ticket['id'];
        $template_client->notes_message = $notesMessageClient;
        $template_client->site_url = getenv('ROOTDOMAIN');
        $template_client->description = html_entity_decode($ticket['description']);
    }
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
