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
if ($msg_count == false) {
    log_app(LOG_ERR, "IMAP message count failed to query: ".imap_last_error());
}

$failed_email_ids = [];
$succeeded_uids = [];
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

        // These can be safely moved as we don't care about them
        $succeeded_uids[] = imap_uid($mbox, $i); 
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

    
    $message_raw = imap_fetchbody($mbox, $i, "1");

    $order   = array("\r\n", "\n", "\r");
    $replace = '<br />';
    $message = str_replace($order, $replace, $message_raw);

    $msg_is_reply = isset($email_ancestor_id);
    // Parse ticket here
    $subject_split = explode(' ', $subject);

    $subject_ticket_id = count($subject_split) > 1 ? intval($subject_split[1]) : 0;
    if ($msg_is_reply) {
        log_app(LOG_INFO, "Email is a reply");
        $email_exists_query = <<<STR
        SELECT tickets.id
            FROM tickets
            JOIN ticket_email_ids ON ticket_email_ids.ticket_id = tickets.id
            WHERE ticket_email_ids.email_id = '$email_ancestor_id'
        STR;
        $email_exists_result = mysqli_query($database, $email_exists_query);
        $email_exists_data = mysqli_fetch_assoc($email_exists_result);

        if (isset($email_exists_data["id"])) {
            $existing_ticket_id = intval($email_exists_data["id"]);
            // add note on existing ticket
            add_note_with_filters($existing_ticket_id, $sender_username, $message, 1, true, null, $email_msg_id);
        } else {
            $ancestor_exists_query = "SELECT linked_id FROM notes WHERE email_msg_id = '$email_ancestor_id'";
            $ticket_exists_result = mysqli_query($database, $ancestor_exists_query);
            $ticket_exists_data = mysqli_fetch_assoc($ticket_exists_result);

            if (isset($ticket_exists_data["linked_id"])) {
                $existing_ticket_id = intval($ticket_exists_data["linked_id"]);
                add_note_with_filters($existing_ticket_id, $sender_username, $message, 1, true, null, $email_msg_id);
            } else {
                $failed_email_ids[] = $i;
                log_app(LOG_ERR, "Failed to find ancestor id in database for message \"$email_msg_id\".This should never happen");
            }
        }
    } else {
        log_app(LOG_INFO, "Email is NOT a reply");
        if (strtolower($subject_split[0]) != "ticket" ||  $subject_ticket_id <= 0 || count($subject_split) != 2)
        {
            $receipt_ticket_id = -1;
            // Check if the user is in the local database. If the value isn't in failed_email_ids, they exist in local db
            if (!in_array($i, $failed_email_ids)) {
                $res = create_ticket($sender_username, $subject, $message, $email_msg_id, $receipt_ticket_id);
                if (!$res || $receipt_ticket_id == -1) {
                    log_app(LOG_ERR, "Failed to create ticket from $sender_username");
                    $failed_email_ids[] = $i;
                }

                $receipt_subject = "Ticket $receipt_ticket_id";
                $template = new Template(from_root("/includes/templates/ticket_creation_receipt.phtml"));
                $template->ticket_id = $receipt_ticket_id;
                $template->site_url = getenv('ROOTDOMAIN');

                send_email_and_add_to_ticket($receipt_ticket_id, $sender_email, $receipt_subject, $template);
            }
        } else {
            // ticket syntax is valid, add a note on that ticket
            add_note_with_filters($subject_ticket_id, $sender_username, $message, 1, true, null);
        }
    }
    if (in_array($i, $failed_email_ids))
        log_app(LOG_INFO, "Failed to parse email from $sender_email");
    else {
        log_app(LOG_INFO, "Successfully parsed email from $sender_email");
        $succeeded_uids[] = imap_uid($mbox, $i);
    }
}

$parsed_emails = 0;
$moved_emails = 0;

// Move parsed emails to important folder/label if we didn't have a parsing error
foreach ($succeeded_uids as $uid) {
    // Move email to important box
    if ($move_emails_after_parsed) {
        $msg_move_result = imap_mail_move($mbox, $uid, "[Gmail]/Important", CP_UID);
        if (!$msg_move_result) {
            log_app(LOG_ERR, "Failed to move message: ".imap_last_error());
        } else {
            $moved_emails++;
        }
    }
    $parsed_emails++;
}

imap_expunge($mbox);
imap_close($mbox);
?>
Successfully parsed <?= $parsed_emails ?> emails, and moved <?= $moved_emails ?> emails. (These should be the same)<br>
<?= count($failed_email_ids) ?> emails failed to parse.