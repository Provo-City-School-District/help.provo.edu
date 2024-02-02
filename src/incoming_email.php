<?php
require_once("helpdbconnect.php");
require_once("functions.php");
require_once("authentication_utils.php");
require_once("ticket_utils.php");
require_once("email_utils.php");
require_once("template.php");

$move_emails_after_parsed = true;

$imap_path = '{imap.gmail.com:993/imap/ssl}INBOX';
$username = getenv("GMAIL_USER");
$password = getenv("GMAIL_PASSWORD");

$mbox = imap_open($imap_path, $username, $password) or die('Cannot connect to Gmail: ' . imap_last_error());
$msg_count = imap_num_msg($mbox);

$failed_email_ids = [];
// iterate through the messages in inbox
for ($i = 1; $i <= $msg_count; $i++) {
    $header = imap_headerinfo($mbox, $i);
    if (!$header) {
        log_app(LOG_ERR, "Failed to get header info for email");
    }
    $email_msg_id = $header->message_id;
    $email_ancestor_id = $header->in_reply_to;
    $from_host = strtolower($header->from[0]->host);
    $sender_username = $header->from[0]->mailbox;
    $sender_email = strtolower($sender_username.'@'.$from_host);
    $subject = $header->subject;

    // Ignore non district emails
    if (($from_host != "provo.edu")) {
        log_app(LOG_INFO, "Received email from $sender_email, ignoring...");
        continue;
    }

    if (!user_exists_locally($sender_username)) {
        create_user_in_local_db($sender_username);

        // Check again that the user was successfully created
        if (!user_exists_locally($sender_username)) {
            log_app(LOG_ERR, "Failed to create local user $sender_username");
            $failed_email_ids[] = $i;
        }
    }

    $message = imap_fetchbody($mbox, $i, 1);

    $msg_is_reply = isset($email_ancestor_id);
    // Parse ticket here
    $subject_split = explode(' ', $subject);

    $subject_ticket_id = count($subject_split) > 1 ? intval($subject_split[1]) : 0;
    if ($msg_is_reply) {
        $ancestor_exists_query = "SELECT id, email_msg_id FROM tickets WHERE email_msg_id = '$email_ancestor_id'";
        $ticket_exists_result = mysqli_query($database, $ancestor_exists_query);
        $ticket_exists_data = mysqli_fetch_assoc($ticket_exists_result);

        if (isset($ticket_exists_data["email_msg_id"])) {
            $existing_ticket_id = intval($ticket_exists_data["id"]);
            // add note on existing ticket
            add_note_with_filters($existing_ticket_id, $sender_username, $message, 1, true);
        } else {
            $failed_email_ids[] = $i;
        }
    } else {
        if (strtolower($subject_split[0]) != "ticket" ||  $subject_ticket_id <= 0 || count($subject_split) != 2)
        {
            $receipt_ticket_id = -1;
            // Check if the user is in the local database. If the value isn't in failed_email_ids, they exist in local db
            if (!in_array($i, $failed_email_ids)) {
                $res = create_ticket($sender_username, $subject, $message, $email_msg_id, $receipt_ticket_id);
                if (!$res || $receipt_ticket_id == -1) {
                    $failed_email_ids[] = $i;
                }

                $receipt_subject = "Ticket $receipt_ticket_id";
                $template = new Template(from_root("/includes/templates/ticket_creation_receipt.phtml"));
                $template->ticket_id = $receipt_ticket_id;

                // send_email($sender_email, $receipt_subject, $template);
            }
        } else {
            // ticket syntax is valid, add a note on that ticket
            add_note_with_filters($subject_ticket_id, $sender_username, $message, 1, true);
        }
    }

    log_app(LOG_INFO, "Successfully parsed email from $sender_email");
}


// Move parsed emails to important folder/label if we didn't have a parsing error
if ($move_emails_after_parsed && $msg_count > 0) {
    for ($i = 1; $i <= $msg_count; $i++) {

        // Check if this email had a parsing error
        if (in_array($i, $failed_email_ids)) {
            continue;
        }

        // Move email to important box
        $msg_move_result = imap_mail_move($mbox, strval($i), "[Gmail]/Important");
        if (!$msg_move_result) {
            log_app(LOG_WARN, "Failed to move message: ".imap_last_error());
        }
    }
}

imap_close($mbox);
?>
Parsed emails.