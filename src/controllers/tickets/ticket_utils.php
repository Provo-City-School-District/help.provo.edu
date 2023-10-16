<?php
// DB connection can fail if not included first, TODO fix maybe

function email_if_valid(string $email)
{
    $clean_email = filter_var($email, FILTER_SANITIZE_EMAIL);

    if (filter_var($clean_email, FILTER_VALIDATE_EMAIL)) {
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


// Function to sanitize numeric
function sanitize_numeric_input($input)
{
    // Validate the input
    if (isset($input) && is_numeric($input)) {
        $input = (int) $input;
    } else {
        // Invalid ticket ID, redirect to error page. this page isn't created yet, but we may change how this is handled.
        header("Location: error.php");
        exit;
    }

    // Sanitize the input
    $input = filter_var($input, FILTER_SANITIZE_NUMBER_INT);

    return $input;
}
