<?php
include("header.php");
require_once("helpdbconnect.php");
require_once("functions.php");
// TESTING CODE + FILE

function create_local_user(string $email)
{
    $ldap_host = getenv('LDAPHOST');
    $ldap_port = getenv('LDAPPORT');
    $ldap_conn = ldap_connect($ldap_host, $ldap_port);
    if (!$ldap_conn) {
        die('Could not connect to LDAP server');
    }

    // anonymous bind
    $ldap_bind = ldap_bind($ldap_conn, 'psd\\', "");
    if (!$ldap_bind) {
        die("Could not bind to LDAP server");
    }

    $ldap_dn = getenv('LDAP_DN');
    $ldap_user = getenv('LDAP_USER');
    $ldap_password = getenv('LDAP_PASS');

    // Search LDAP directory
    $search = '(&(objectCategory=person)(objectClass=user)(sAMAccountName=' . $input_username . '))'; // Your search filter
    $ldap_result = ldap_search($ldap_conn, $ldap_dn, $search);
    if (!$ldap_result) {
        die('LDAP search failed.');
    }

    $ldap_result = ldap_get_entries($ldap_conn, $ldap_result);
    if (!$ldap_result)
        die("LDAP result failed");

    // Loop through results
    for ($i = 0; $i < $ldap_result['count']; $i++) {
        $username = $ldap_result[$i]['samaccountname'][0];
        $firstname = $ldap_result[$i]['givenname'][0];
        $lastname = $ldap_result[$i]['sn'][0];
        $email = $ldap_result[$i]['mail'][0];
        $employee_id = $ldap_result[$i]['employeeid'][0];
        echo $username.$firstname.$lastname.$email.$employee_id;
    }
}
$move_emails_after_parsed = false;

$imap_path = '{imap.gmail.com:993/imap/ssl}INBOX';
$username = getenv("GMAIL_USER");
$password = getenv("GMAIL_PASSWORD");

$mbox = imap_open($imap_path, $username, $password) or die('Cannot connect to Gmail: ' . imap_last_error());

$msg_count = imap_num_msg($mbox);
echo "Msg count: ".$msg_count."<br><br>";

// iterate through the messages in inbox
for ($i = 1; $i <= $msg_count; $i++) {
    $header = imap_headerinfo($mbox, $i);
    $from_host = strtolower($header->from[0]->host);
    
    $sender = strtolower($header->from[0]->mailbox.'@'.$from_host);
    $subject = $header->subject;

    // Ignore non district emails
    if (($from_host != "provo.edu"))
        continue;

    if (!user_exists_locally($sender)) {
        echo "User does not exist locally.";
        //create_local_user($sender);
        continue;
    } else {
        echo "User exists";
    }

    // Parse ticket here
    $subject_split = explode(' ', $subject);
    $subject_ticket_id = intval($subject_split[1]);
    if (strtolower($subject_split[0]) != "ticket" || 
        $subject_ticket_id <= 0 ||
        count($subject_split) != 2)
        continue;


    $message = imap_fetchbody($mbox, $i, 1);
    echo $subject_ticket_id.$message."<br><br>";

    // create note. maybe modify add_note_handler.php or copy most of its code.
    // we will want to minimize code duplication though
}


// Move parsed emails to important folder/label
if ($move_emails_after_parsed && $msg_count > 0) {
    $msg_move_result = imap_mail_move($mbox, "1:".strval($msg_count), "[Gmail]/Important");
    if (!$msg_move_result) {
        echo "Failed to move message: ".imap_last_error();
    }
}

imap_close($mbox);
?>
<br><br><br>
----------------------<br>
End of messages