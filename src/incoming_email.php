<?php
include("header.php");
// TESTING CODE + FILE


$imap_path = '{imap.gmail.com:993/imap/ssl}INBOX';
$username = getenv("GMAIL_USER");
$password = getenv("GMAIL_PASSWORD");

$mbox = imap_open($imap_path, $username, $password, OP_READONLY) or die('Cannot connect to Gmail: ' . imap_last_error());

// get information about the current mailbox (INBOX in this case)
$mboxCheck = imap_check($mbox);

$msg_count = $mboxCheck->Nmsgs;

// iterate trough those messages
for ($i = 1; $i <= $msg_count; $i++) {
    $header = imap_headerinfo($mbox, $i);
    $from_host = $header->from[0]->host;
    
    $sender = $header->from[0]->mailbox . "@" . $from_host;
    $subject = $header->subject;
    echo $header->recent;
    $content = imap_body($mbox, $i);

    // Ignore non district emails
    //if ((strtolower($from_host) != "provo.edu"))
    //    continue;

    echo preg_replace( "/\n\s+/", "\n", rtrim(html_entity_decode(strip_tags($content))));
    echo "<br><br>";

}

imap_close($mbox);

echo "==end==";