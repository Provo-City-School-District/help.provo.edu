<?php
include("header.php");
require_once("helpdbconnect.php");
require_once("functions.php");
require_once("authentication_utils.php");
require_once("ticket_utils.php");
// TESTING CODE + FILE


function create_user_in_local_db($username)
{
    global $database;

    $ldap_dn = getenv('LDAP_DN');
    $ldap_user = getenv('LDAP_USER');
    $ldap_password = getenv('LDAP_PASS');

    $ldap_host = getenv('LDAPHOST');
    $ldap_port = getenv('LDAPPORT');
    $ldap_conn = ldap_connect($ldap_host, $ldap_port);
    if (!$ldap_conn) {
        log_app(LOG_ERR, "Failed to create LDAP connection");
    }

    // anonymous bind
    $ldap_bind = ldap_bind($ldap_conn, $ldap_user, $ldap_password);
    if (!$ldap_bind) {
        log_app(LOG_ERR, "LDAP bind failed.");
    }

    $search = "(&(objectCategory=person)(objectClass=user)(sAMAccountName=$username))";
    $ldap_search_result = ldap_search($ldap_conn, $ldap_dn, $search);
    if (!$ldap_search_result) {
        log_app(LOG_ERR, 'LDAP search failed.');
    }
    
    $ldap_entries_result = ldap_get_entries($ldap_conn, $ldap_search_result);
    if (!$ldap_entries_result) {
        log_app(LOG_ERR, "LDAP get entries failed.");
    }

    if ($ldap_entries_result['count'] == 0) {
        log_app(LOG_ERR, "User '$username' was not found in LDAP");
    }

    for ($i = 0; $i < $ldap_entries_result['count']; $i++) {
        $username = $ldap_entries_result[$i]['samaccountname'][0];
        $firstname = $ldap_entries_result[$i]['givenname'][0];
        $lastname = $ldap_entries_result[$i]['sn'][0];
        $email = $ldap_entries_result[$i]['mail'][0];
        $employee_id = $ldap_entries_result[$i]['employeeid'][0];
    }

    $insert_query = "INSERT INTO users (username, email, lastname, firstname, ifasid) VALUES ('" . $username . "', '" . $email . "', '" . $lastname . "', '" . $firstname . "', '" . $employee_id . "')";

    $insert_result = mysqli_query($database, $insert_query);
    if (!$insert_result) {
        $current_mysqli_error = mysqli_error($database);
        log_app(LOG_ERR, "Insert query error: $current_mysqli_error");
    }
}

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
    $from_host = strtolower($header->from[0]->host);
    $sender_username = $header->from[0]->mailbox;
    $sender_email = strtolower($sender_username.'@'.$from_host);
    $subject = $header->subject;

    // Ignore non district emails
    if (($from_host != "provo.edu"))
        continue;

    if (!user_exists_locally($sender_username)) {
        create_user_in_local_db($sender_username);
        if (!user_exists_locally($sender_username)) {
            log_app(LOG_ERR, "Failed to create local user $sender_username");
            $failed_email_ids[] = $i;
        }
    }

    $message = imap_fetchbody($mbox, $i, 1);

    // Parse ticket here
    $subject_split = explode(' ', $subject);
    $subject_ticket_id = intval($subject_split[1]);
    if (strtolower($subject_split[0]) != "ticket" || 
        $subject_ticket_id <= 0 ||
        count($subject_split) != 2)
    {
        // create a ticket with their subject
        // TODO
    } else {
        // ticket syntax is valid, add a note on that ticket
        add_note_with_filters($subject_ticket_id, $sender_username, $message, 1, true);
    }

    log_app(LOG_INFO, "Successfully parsed email from $sender_email");
}


// Move parsed emails to important folder/label if we didn't have a parsing error
if ($move_emails_after_parsed && $msg_count > 0) {
    for ($i = 1; $i <= $msg_count; $i++) {

        // Check if this email had a parsing error
        if (in_array($i, $failed_email_ids))
            continue;

        // Move email to important box
        $msg_move_result = imap_mail_move($mbox, strval($i), "[Gmail]/Important");
        if (!$msg_move_result) {
            log_app(LOG_WARN, "Failed to move message: ".imap_last_error());
        }
    }
}

imap_close($mbox);
?>