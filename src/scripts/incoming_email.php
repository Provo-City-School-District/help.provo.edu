<?php
require_once("helpdbconnect.php");
require_once("functions.php");
require_once("authentication_utils.php");
require_once("ticket_utils.php");
require_once("email_utils.php");
require_once("template.php");

$blacklisted_emails = [
    "dev@provo.edu",
    "help@provo.edu",
    "helpdesk@provo.edu",
    "smtp@provo.edu",
    "server@provo.edu"
];

$move_emails_after_parsed = true;

$imap_path = '{imap.gmail.com:993/imap/ssl}INBOX';
$username = getenv("GMAIL_USER");
$password = getenv("GMAIL_PASSWORD");

$mbox = imap_open($imap_path, $username, $password) or die('Cannot connect to Gmail: ' . imap_last_error());
$msg_count = imap_num_msg($mbox);
if ($msg_count == false) {
    log_app(LOG_ERR, "IMAP message count failed to query: " . imap_last_error());
    exit;
}

// Sort by msg date
imap_sort($mbox, SORTDATE, false);


$failed_email_ids = [];
$succeeded_uids = [];

function format_html($str)
{
    // Convertit tous les caractères éligibles en entités HTML en convertissant les codes ASCII 10 en $lf
    $str = htmlentities($str, ENT_COMPAT, "UTF-8");
    $str = str_replace(chr(10), "<br>", $str);
    return $str;
}

// Thanks https://stackoverflow.com/a/40419584
function mail_is_auto_submitted($mailbox, $msg_id)
{
    $header = imap_fetchheader($mailbox, $msg_id);
    $header_identifiers = array('Auto-Submitted:([\s]*)auto-([replied|notified|generated])');

    for ($x = 0; $x < count($header_identifiers); $x++) {
        if (preg_match('/' . $header_identifiers[$x] . '/is', $header)) {
            return true;
        }
    }

    return false;
}

// iterate through the messages in inbox

for ($i = 1; $i <= $msg_count; $i++) {
    $header = imap_headerinfo($mbox, $i);
    if (!$header) {
        log_app(LOG_ERR, "Failed to get header info for email");
    }
    $email_msg_id = $header->message_id;
    $from_host = strtolower($header->from[0]->host);
    $sender_username = $header->from[0]->mailbox;
    $sender_email = strtolower($sender_username . '@' . $from_host);
    $subject = isset($header->subject) ? $header->subject : "";

    $email_ancestor_id = null;
    if (property_exists($header, "in_reply_to")) {
        $email_ancestor_id = $header->in_reply_to;
    }

    // Get incoming cc emails from email
    $incoming_cc_emails = [];
    if (isset($header->cc)) {
        foreach ($header->cc as $cc_email) {
            $incoming_cc_emails[] = $cc_email->mailbox . '@' . $cc_email->host;
        }
    }


    // Ignore blacklisted emails
    if (in_array($sender_email, $blacklisted_emails)) {
        log_app(LOG_INFO, "Received email from $sender_email but it is on the blacklist. Ignoring...");

        // These can be safely moved as we don't care about them
        $succeeded_uids[] = imap_uid($mbox, $i);
        continue;
    }

    // Ignore auto-reply emails
    if (mail_is_auto_submitted($mbox, $i)) {
        log_app(LOG_INFO, "Ignoring email from $sender_email as it is an auto-reply..");
        // These can be safely moved as we don't care about them
        $succeeded_uids[] = imap_uid($mbox, $i);
        continue;
    }

    $msg_is_external = false;

    // Detect non district emails
    if ($from_host != "provo.edu") {
        $msg_is_external = true;
        $sender_username = "External sender: $sender_email";
    }

    if (!user_exists_locally($sender_username)) {
        create_user_in_local_db($sender_username);

        // Check again that the user was successfully created
        if (!user_exists_locally($sender_username)) {
            log_app(LOG_ERR, "Failed to create local user $sender_username. Ignoring...");
            //$failed_email_ids[] = $i;
        }
    }

    // check department of sender
    $department_id = get_user_department($sender_username);
    $department_id = is_numeric($department_id) ? intval($department_id) : null;

    // Thanks https://stackoverflow.com/a/43181298
    $obj_structure = imap_fetchstructure($mbox, $i);

    $obj_section = $obj_structure;
    $section = "1";
    for ($j = 0; $j < 10; $j++) {
        if ($obj_section->type == 0) {
            break;
        } else {
            $obj_section = $obj_section->parts[0];
            $section .= ($j > 0 ? ".1" : "");
        }
    }
    $text = imap_fetchbody($mbox, $i, $section);

    if ($obj_section->encoding == 3) {
        $text = imap_base64($text);
    } else if ($obj_section->encoding == 4) {
        $text = imap_qprint($text);
    }

    foreach ($obj_section->parameters as $obj_param) {
        if (($obj_param->attribute == "charset") && (mb_strtoupper($obj_param->value) != "UTF-8")) {
            $text = utf8_encode($text);
            break;
        }
    }

    $text = strip_tags($text);
    $message = preg_replace('#(^\w.+:\n)?(^>.*(\n|$))+#mi', "", nl2br($text));

    $is_forward = str_starts_with($subject, "Fwd:");
    $msg_is_reply = isset($email_ancestor_id) && !$is_forward;
    // Parse ticket here
    $subject_split = explode(' ', $subject);
    $operating_ticket = -1;

    if ($msg_is_reply) {
        log_app(LOG_INFO, "Email is a reply");
        $email_exists_query = <<<STR
        SELECT tickets.id
            FROM tickets
            JOIN ticket_email_ids ON ticket_email_ids.ticket_id = tickets.id
            WHERE ticket_email_ids.email_id = ?
        STR;
        $email_exists_result = HelpDB::get()->execute_query($email_exists_query, [$email_ancestor_id]);
        $email_exists_data = mysqli_fetch_assoc($email_exists_result);

        if (isset($email_exists_data["id"])) {
            $existing_ticket_id = intval($email_exists_data["id"]);
            $operating_ticket = $existing_ticket_id;

            if ($msg_is_external && !email_is_referenced_on_ticket($sender_email, $operating_ticket)) {
                log_app(LOG_INFO, "Email $sender_email doesn't have permission to add a note to ticket $operating_ticket (not found in CC/BCC emails)");
                continue;
            }


            // add note on existing ticket
            create_note($existing_ticket_id, $sender_username, $message, 0, 0, 0, 0, true, $department_id, null, $email_msg_id);
        } else {
            $ticket_exists_result = HelpDB::get()->execute_query("SELECT linked_id FROM notes WHERE email_msg_id = ?", [$email_ancestor_id]);
            $ticket_exists_data = mysqli_fetch_assoc($ticket_exists_result);

            if (isset($ticket_exists_data["linked_id"])) {
                $existing_ticket_id = intval($ticket_exists_data["linked_id"]);
                $operating_ticket = $existing_ticket_id;

                if ($msg_is_external && !email_is_referenced_on_ticket($sender_email, $operating_ticket)) {
                    log_app(LOG_INFO, "Email $sender_email doesn't have permission to add a note to ticket $operating_ticket (not found in CC/BCC emails)");
                    continue;
                }

                create_note($existing_ticket_id, $sender_username, $message, 0, 0, 0, 0, true, $department_id, null, $email_msg_id);
            } else {
                $failed_email_ids[] = $i;
                log_app(LOG_ERR, "Failed to find ancestor id ( \"$email_ancestor_id\" ) in database for message \"$email_msg_id\". This shouldn't happen on a receipt email we sent out.");
            }
        }
    } else {
        $subject_ticket_id = count($subject_split) > 1 ? intval($subject_split[1]) : 0;
        log_app(LOG_INFO, "Email is NOT a reply");
        if (strtolower($subject_split[0]) != "ticket" ||  $subject_ticket_id <= 0 || count($subject_split) != 2) {
            // Early return to prevent external emails from creating tickets
            if ($msg_is_external)
                continue;

            $receipt_ticket_id = -1;
            // Check if the user is in the local database. If the value isn't in failed_email_ids, they exist in local db
            if (!in_array($i, $failed_email_ids)) {
                $location_code = get_client_location($sender_username);
                log_app(LOG_INFO, "Client location code is $location_code");
                $res = create_ticket($sender_username, limitChars($subject, 100), $message, $email_msg_id, $location_code, $receipt_ticket_id);
                if (!$res || $receipt_ticket_id == -1) {
                    log_app(LOG_ERR, "Failed to create ticket from $sender_username");
                    $failed_email_ids[] = $i;
                }

                $receipt_subject = "Ticket $receipt_ticket_id - $subject";
                $template = new Template(from_root("/includes/templates/ticket_creation_receipt.phtml"));
                $template->ticket_id = $receipt_ticket_id;
                $template->description = $subject;
                $template->site_url = getenv('ROOTDOMAIN');

                $res = send_email_and_add_to_ticket($receipt_ticket_id, $sender_email, $receipt_subject, $template);
                if (!$res) {
                    log_app(LOG_ERR, "Failed to send email to $sender_email");
                }
                $operating_ticket = $receipt_ticket_id;

                // Log the ticket creation
                logTicketChange(HelpDB::get(), $receipt_ticket_id, 'System', 'created', '', '');

                $incoming_cc_emails_str = mysqli_real_escape_string(HelpDB::get(), implode(',', $incoming_cc_emails));
                $res = HelpDB::get()->execute_query("UPDATE help.tickets SET cc_emails = ? WHERE id = ?", [$incoming_cc_emails_str, $receipt_ticket_id]);
            }
        } else {
            $operating_ticket = $subject_ticket_id;
            if ($msg_is_external && !email_is_referenced_on_ticket($sender_email, $operating_ticket)) {
                log_app(LOG_INFO, "Email $sender_email doesn't have permission to add a note to ticket $operating_ticket (not found in CC/BCC emails)");
                continue;
            }

            // ticket syntax is valid, add a note on that ticket
            create_note($subject_ticket_id, $sender_username, $message, 0, 0, 0, 0, true, get_user_department($sender_username), null);
        }
    }

    // Add any attachments on operating_ticket
    if ($operating_ticket != -1) {
        find_and_upload_attachments($operating_ticket, $mbox, $i, $sender_username);
    }
    if (in_array($i, $failed_email_ids)) {
        log_app(LOG_INFO, "Failed to parse email from $sender_email");
    } else {
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
            log_app(LOG_ERR, "Failed to move message: " . imap_last_error());
        } else {
            $moved_emails++;
        }
    }
    $parsed_emails++;
}

imap_expunge($mbox);
imap_close($mbox);

function find_and_upload_attachments(int $ticket_id, IMAP\Connection $mbox, int $msg_num, $sender_username)
{
    /* get mail structure */
    $structure = imap_fetchstructure($mbox, $msg_num);

    $attachments = [];

    /* if any attachments found... */
    if (isset($structure->parts) && count($structure->parts)) {
        for ($i = 0; $i < count($structure->parts); $i++) {
            $attachments[$i] = array(
                'is_attachment' => false,
                'filename' => '',
                'name' => '',
                'attachment' => ''
            );

            if ($structure->parts[$i]->ifdparameters) {
                foreach ($structure->parts[$i]->dparameters as $object) {
                    if (strtolower($object->attribute) == 'filename') {
                        $attachments[$i]['is_attachment'] = true;
                        $attachments[$i]['filename'] = $object->value;
                    }
                }
            }

            if ($structure->parts[$i]->ifparameters) {
                foreach ($structure->parts[$i]->parameters as $object) {
                    if (strtolower($object->attribute) == 'name') {
                        $attachments[$i]['is_attachment'] = true;
                        $attachments[$i]['name'] = $object->value;
                    }
                }
            }

            if ($attachments[$i]['is_attachment']) {
                $attachments[$i]['attachment'] = imap_fetchbody($mbox, $msg_num, $i + 1);

                /* 3 = BASE64 encoding */
                if ($structure->parts[$i]->encoding == 3) {
                    $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                }
                /* 4 = QUOTED-PRINTABLE encoding */ elseif ($structure->parts[$i]->encoding == 4) {
                    $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                }
            }
        }
    }


    $insertQuery = "SELECT attachment_path from help.tickets WHERE id = ?";
    $stmt = mysqli_prepare(HelpDB::get(), $insertQuery);
    mysqli_stmt_bind_param($stmt, "i", $ticket_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    //log_app(LOG_INFO, var_dump($result));
    if (!$result) {
        log_app(LOG_ERR, "Failed to get old attachment_path");
    }
    mysqli_stmt_close($stmt);

    $uploadPaths = null;
    $old_uploadPaths = explode(',', $result["attachment_path"]);

    if ($old_uploadPaths != null && count($old_uploadPaths) > 0) {
        $uploadPaths = $old_uploadPaths;
    } else {
        $uploadPaths = [];
    }

    /* iterate through each attachment and save it */
    $wrote_filenames = [];
    foreach ($attachments as $attachment) {
        if ($attachment['is_attachment'] == 1) {
            $filename = null;
            if (!isset($attachment['name']))
                $filename = date('Ymd_Hi') . $attachment['filename'];
            else
                $filename = date('Ymd_Hi') . $attachment['name'];

            $uploadPath = "/../uploads/" . $ticket_id . "-" . $filename;
            log_app(LOG_INFO, "Uploading image to " . from_root($uploadPath));
            $fp = fopen(from_root($uploadPath), "w+");
            fwrite($fp, $attachment['attachment']);
            fclose($fp);
            $uploadPaths[] = $uploadPath;
            $wrote_filenames[] = $filename;
        }
    }
    $attachmentPath = count($uploadPaths) > 0 ? implode(',', $uploadPaths) : "";

    $insertQuery = "UPDATE help.tickets SET attachment_path = ? WHERE id = ?";
    $stmt = mysqli_prepare(HelpDB::get(), $insertQuery);
    mysqli_stmt_bind_param($stmt, "si", $attachmentPath, $ticket_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $field_name = 'Attachment';
    $old_value = 'NA';
    $changed_string = "";
    foreach ($wrote_filenames as $filename) {
        $changed_string .= $filename . ", ";
    }
    logTicketChange(HelpDB::get(), $ticket_id, $sender_username, $field_name, $old_value, $changed_string);
}

function email_is_referenced_on_ticket(string $sender_email, int $ticket_id)
{
    $cc_emails = explode(',', field_for_ticket($ticket_id, "cc_emails"));
    $bcc_emails = explode(',', field_for_ticket($ticket_id, "bcc_emails"));

    $combined = array_merge($cc_emails, $bcc_emails);

    return in_array($sender_email, $combined);
}
?>
Successfully parsed <?= $parsed_emails ?> emails, and moved <?= $moved_emails ?> emails. (These should be the same)<br>
<?= count($failed_email_ids) ?> emails failed to parse.