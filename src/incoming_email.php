<?php
include("includes/header.php");

// handles input validation
function validate_incoming_email(
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

$imap_path = '{imap.gmail.com:993/imap/ssl}INBOX';
$username = 'dev@provo.edu';
$password = 'fatigue7endogamy!hacienda1thin6patio9upward8honeydew8FIGHT8avarice0culprit';

$mbox = imap_open($imap_path, $username, $password, OP_READONLY) or die('Cannot connect to Gmail: ' . imap_last_error());

// get information about the current mailbox (INBOX in this case)
$mboxCheck = imap_check($mbox);

$msg_count = $mboxCheck->Nmsgs;

// iterate trough those messages
for ($i = 1; $i <= $msg_count; $i++) {
    $header = imap_headerinfo($mbox, $i);

    $from_addr = $header->from[0]->mailbox . "@" . $header->from[0]->host;

    parse_incoming_email($from_addr, $subject, $message);
}

imap_close($mbox);

echo "==end==";