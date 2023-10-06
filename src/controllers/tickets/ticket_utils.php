<?php
// I dont think this is required here. I think it is already included in the pages we are including this file into 
//require_once('../../includes/helpdbconnect.php');

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

// Function to check if there is an excluded date between two dates
function hasExcludedDate($start_date, $end_date)
{
    global $database;
    $exclude_query = "SELECT COUNT(*) FROM exclude_days WHERE exclude_day BETWEEN '{$start_date}' AND '{$end_date}'";
    $exclude_result = mysqli_query($database, $exclude_query);
    $count = mysqli_fetch_array($exclude_result)[0];
    return $count;
}
// Function to check if a date falls on a weekend
function isWeekend($date)
{
    $dayOfWeek = $date->format('N');
    return ($dayOfWeek == 6 || $dayOfWeek == 7);
}

?>