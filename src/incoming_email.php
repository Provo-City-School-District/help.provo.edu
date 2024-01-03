<?php
include("header.php");
require_once("helpdbconnect.php");
require_once("functions.php");
require_once("authentication_utils.php");
require_once("ticket_utils.php");
// TESTING CODE + FILE


function create_user_in_local_db($username)
{
    $ldap_dn = getenv('LDAP_DN');
    $ldap_user = getenv('LDAP_USER');
    $ldap_password = getenv('LDAP_PASS');

    $ldap_host = getenv('LDAPHOST');
    $ldap_port = getenv('LDAPPORT');
    $ldap_conn = ldap_connect($ldap_host, $ldap_port);
    if (!$ldap_conn) {
        die("Failed to connect to LDAP server");
    }

    // anonymous bind
    $ldap_bind = ldap_bind($ldap_conn, $ldap_user, $ldap_password);
    if (!$ldap_bind) {
        die("LDAP bind failed.");
    }

    $search = "(&(objectCategory=person)(objectClass=user)(sAMAccountName=$username))";
    $ldap_search_result = ldap_search($ldap_conn, $ldap_dn, $search);
    if (!$ldap_search_result) {
        die('LDAP search failed.');
    }
    
    $ldap_entries_result = ldap_get_entries($ldap_conn, $ldap_search_result);
    if (!$ldap_entries_result) {
        die("LDAP get entries failed.");
    }

    if ($ldap_entries_result['count'] == 0) {
        die("User '$username' was not found in LDAP");
    }

    for ($i = 0; $i < $ldap_entries_result['count']; $i++) {
        $username = $ldap_entries_result[$i]['samaccountname'][0];
        $firstname = $ldap_entries_result[$i]['givenname'][0];
        $lastname = $ldap_entries_result[$i]['sn'][0];
        $email = $ldap_entries_result[$i]['mail'][0];
        $employee_id = $ldap_entries_result[$i]['employeeid'][0];
    }

    $insert_query = "INSERT INTO users (username, email, lastname, firstname, ifasid) VALUES ('" . $input_username . "', '" . $email . "', '" . $lastname . "', '" . $firstname . "', '" . $employee_id . "')";

    //mysqli_query($database, $insert_query);
    $insert_result = mysqli_query($database, $insert_query);
    if (!$insert_result) {
        echo 'Insert query error: ' . mysqli_error($database);
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
    $sender_user = $header->from[0]->mailbox;
    $sender = strtolower($sender_user.'@'.$from_host);
    $subject = $header->subject;

    // Ignore non district emails
    if (($from_host != "provo.edu"))
        continue;

    if (!user_exists_locally($sender_user)) {
        create_user_in_local_db($sender_user);
        if (!user_exists_locally($sender_user)) {
            die("Failed to create local user");
        }
    } else {
        echo "user exists";
    }

    // Parse ticket here
    $subject_split = explode(' ', $subject);
    $subject_ticket_id = intval($subject_split[1]);
    if (strtolower($subject_split[0]) != "ticket" || 
        $subject_ticket_id <= 0 ||
        count($subject_split) != 2)
        continue;


    $message = imap_fetchbody($mbox, $i, 1);
    echo "ticket id: $subject_ticket_id<br>message:$message<br><br>";

    // create note. using $_POST like this is hacky
    add_note_with_filters($subject_ticket_id, $sender_user, $message, 1, true);

    echo "added note";
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