<?php
require(from_root("/vendor/autoload.php"));
require_once("functions.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


function email_address_from_username(string $username)
{
    return $username."@provo.edu";
}

/*
Returns true on success, false otherwise
*/
function send_email(
    string $recipient,
    string $subject,
    string $message,
    array $cc_recipients = [],
    array $bcc_recipients = [],
    string &$messageID = null
    )
{

    // Create a new PHPMailer instance
    $mailer = new PHPMailer(true);

    try {
        // Set the mailer to use SMTP
        $mailer->isSMTP();

        // Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $mailer->SMTPDebug = 0;

        $mailer->Host = 'smtp.provo.edu';
        $mailer->Port = 25;
        $mailer->setFrom(getenv("GMAIL_USER"), 'dev.provo.edu');
       
       // handle multiple recipients
        $rec_emails = explode(",", $recipient);
        foreach ($rec_emails as $rec_email) {
            $mailer->addAddress($rec_email);
        }
        
        $mailer->isHTML(true); // Set to true for HTML emails

        // Set the actual content of the email
        $mailer->Subject = $subject;

        // Make sure line is 70 chars max and uses \r\n (RFC 2822)
        $email_body = wordwrap($message, 70, "\r\n");
        $mailer->Body = $email_body;

        if ($cc_recipients) {
            foreach ($cc_recipients as $cc_recipient) {
                $mailer->addCC($cc_recipient);
            }
        }   

        if ($bcc_recipients) {
            foreach ($bcc_recipients as $bcc_recipient) {
                $mailer->addBCC($bcc_recipient);
            }
        }

        // Send the email
        $mailer->send();
        $messageID = $mailer->getLastMessageID();
        log_app(LOG_INFO, "Successfully sent email to \"$recipient\" with messageID $messageID");
    } catch (Exception $e) {
        log_app(LOG_ERR, "Caught exception when trying to send email: $e");
        return false;
    }

    return true;
}

function send_email_and_add_to_ticket(
    int $ticket_id,
    string $recipient,
    string $subject,
    string $message,
    array $cc_recipients = [],
    array $bcc_recipients = [],
)
{
    global $database;
    $messageID = null;
    send_email($recipient, $subject, $message, $cc_recipients, $bcc_recipients, $messageID);

    if (isset($messageID)) {
        $insert_email_id_query = "INSERT (ticket_id, email_id) INTO ticket_email_ids WHERE (?, ?)";
        $insert_email_id_stmt = mysqli_prepare($database, $insert_email_id_query);

        mysqli_stmt_bind_param($insert_email_id_stmt, "ss", $ticket_id, $messageID);
        mysqli_stmt_execute($insert_email_id_stmt);
        mysqli_stmt_close($insert_email_id_stmt);
        log_app(LOG_INFO, "Added $messageID to list with ticket id $ticket_id");
    } else {
        log_app(LOG_ERR, "MessageID was null. Not adding $ticket_id to ticket_email_ids");
    }
}

//map for email use
$priorityTypes = [1 => "Critical",3 => "Urgent", 5 => "High",10 => "Standard",15 => "Client Response",30 => "Project",60 => "Meeting Support"];