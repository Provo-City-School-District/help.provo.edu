<?php
require_once('../../includes/helpdbconnect.php');

function email_address_from_username(string $username)
{
    return $username."@provo.edu";
}

function send_email(
    string $recipient,
    string $subject,
    string $message)
{
    // Make sure line is 70 chars max and uses \r\n according to PHP docs
    // https://www.php.net/manual/en/function.mail.php
    $message = wordwrap($message, 70, "\r\n");

    $res = mail($recipient, $subject, $message);
    return $res;
}

function email_if_valid(string $email)
{
    $clean_email = filter_var($email, FILTER_SANITIZE_EMAIL);

    if (filter_var($clean_email, FILTER_VALIDATE_EMAIL)){
        return $clean_email;
    } else {
        return null;
    }
}

function split_email_string_to_arr(string $email_str)
{
    $valid_emails = array();
    $invalid_found = false;

    // Check that emails are valid
    $emails_arr = explode(',', $email_str);
    foreach ($emails_arr as $email) {
        $val = email_if_valid($email);
        if ($val) {
            $valid_emails[] = $val;
        } else {
            $invalid_found = true;
        }
    }

    if ($invalid_found) {
        return null;
    }
    return $valid_emails;
}
?>