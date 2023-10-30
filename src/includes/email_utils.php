<?php
require('../../vendor/autoload.php');

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
    array $bcc_recipients = []
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
        $mailer->setFrom('donotreply@provo.edu', 'Do Not Reply');
        $mailer->addAddress($recipient);
        $mailer->isHTML(false); // Set to true for HTML emails

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
    } catch (Exception $e) {
        file_put_contents('php://stdout', $mailer->ErrorInfo);
        return false;
    }

    return true;
}

// handles input validation
function parse_incoming_email(
    string $sender,
    string $subject,
    string $message)
{
    $email_note = [];
    
    // subject "ticket 30" -> 30
    $subject_split = explode(' ', $subject);
    $ticket_value = intval($subject_split[1]);
    if ((strtolower($subject_split[0]) == "ticket") && (count($subject_split) == 2) && ($ticket_value > 0))
        $email_note["ticket"] = $ticket_value;
    else
        $email_note["ticket"] = null;

    $email_note["content"] = trim(htmlspecialchars($message));
    $email_note["sender"] = trim(htmlspecialchars($sender));
    
    return $email_note;
}