<?php
include("header.php");
// TESTING CODE + FILE


$imap_path = '{imap.gmail.com:993/imap/ssl}INBOX';
$username = getenv("GMAIL_USER");
$password = getenv("GMAIL_PASSWORD");

$mbox = imap_open($imap_path, $username, $password) or die('Cannot connect to Gmail: ' . imap_last_error());

$msg_count = imap_num_msg($mbox);
echo "Msg count: ".$msg_count."<br><br>";

// iterate through those messages
for ($i = 1; $i <= $msg_count; $i++) {
    $header = imap_headerinfo($mbox, $i);
    $from_host = $header->from[0]->host;
    
    $sender = $header->from[0]->mailbox . "@" . $from_host;
    $subject = $header->subject;

    $content = imap_body($mbox, $i);

    // Ignore non district emails
    if ((strtolower($from_host) != "provo.edu"))
        continue;

    // Parse ticket here
}

// Move parsed emails to important folder/label
if ($msg_count > 0) {
    $msg_move_result = imap_mail_move($mbox, "1:".strval($msg_count), "[Gmail]/Important");
    if (!$msg_move_result) {
        die("Failed to move message: ".imap_last_error());
    }
}

imap_close($mbox);
?>
<br><br><br>
----------------------<br>
End of messages