<?php
require_once("ticket_utils.php");
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
    $valid_emails = [];
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
function formatFieldName($str)
{
    // Remove underscores and replace with spaces
    $str = str_replace('_', ' ', $str);
    // Capitalize the first letter of each word
    $str = ucwords($str);
    return $str;
}

function add_note_with_filters(
    string $ticket_id, 
    string $username, 
    string $note_content, 
    string $note_time,
    bool $visible_to_client,
    string $date_override = null,
    string $email_msg_id = null,
)
{
    global $database;

    $ticket_id_clean = trim(htmlspecialchars($ticket_id));
    $note_content_clean = trim(htmlspecialchars($note_content));
    $username_clean = trim(htmlspecialchars($username));
    $note_time_clean = trim(htmlspecialchars($note_time));
    $timestamp = date('Y-m-d H:i:s');
    if (intval($note_time_clean) <= 0) {
        return false;
    }

    $visible_to_client_intval = intval($visible_to_client);

    // Insert the new note into the database
    $query = "INSERT INTO notes (linked_id, created, creator, note, time, visible_to_client, date_override, email_msg_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($database, $query);
    mysqli_stmt_bind_param($stmt, "issssiss", $ticket_id_clean, $timestamp, $username_clean, $note_content_clean, $note_time_clean, 
        $visible_to_client_intval, $date_override, $email_msg_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Log the creation of the new note in the ticket_logs table
    $log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at) VALUES (?, ?, ?, NULL, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))";
    $log_stmt = mysqli_prepare($database, $log_query);

    $notecolumn = "note";
    mysqli_stmt_bind_param($log_stmt, "isss", $ticket_id, $username, $notecolumn, $note_content_clean);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
    return true;
}

// Returns true on success, false on failure
function create_ticket(string $client, string $subject, string $content, string $email_msg_id, int &$created_ticket_id)
{
    global $database;

    $client_clean = trim(htmlspecialchars($client));
    $subject_clean = trim(htmlspecialchars($subject));
    $content_clean = trim(htmlspecialchars($content));

        // Create an SQL INSERT query
    $insertQuery = "INSERT INTO tickets (location, room, name, description, created, last_updated, due_date, status, client,attachment_path,phone,cc_emails,bcc_emails,request_type_id,priority)
                VALUES (NULL, NULL, ?, ?, ?, ?, ?, 'open', ?, '', '', '', '', 0, 10)";

    // Prepare the SQL statement
    $stmt = mysqli_prepare($database, $insertQuery);

    if ($stmt === false) {
        log_app(LOG_ERR, 'Error preparing insert query: ' . mysqli_error($database));
        return false;
    }

    $priority = 10;

    $created_time = date("Y-m-d H:i:s");
    // Calculate the due date by adding the priority days to the created date
    $created_date = new DateTime($created_time);
    $due_date = clone $created_date;
    $due_date->modify("+{$priority} weekdays");

    // Check if the due date falls on a weekend or excluded date
    while (isWeekend($due_date)) {
        $due_date->modify("+1 day");
    }
    $count = hasExcludedDate($created_date->format('Y-m-d'), $due_date->format('Y-m-d'));
    if ($count > 0) {
        $due_date->modify("{$count} day");
    }
    // Format the due date as a string
    $due_date = $due_date->format('Y-m-d');

    mysqli_stmt_bind_param(
        $stmt,
        'ssssss',
        $subject_clean,
        $content_clean,
        $created_time,
        $created_time,
        $due_date,
        $client_clean,
    );


    // Execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        log_app(LOG_INFO, "create_ticket success");

        $created_ticket_id = mysqli_insert_id($database);
        add_ticket_msg_id_mapping($email_msg_id, $created_ticket_id);
        return true;
    } else {
        log_app(LOG_ERR, "create_ticket failure");
        return false;
    }

    mysqli_stmt_close($stmt);
}

// Messages for alerts
$alert48Message = "Ticket hasn't been updated in 48 hours";
$pastDueMessage = "Past Due";

//remove alerts from the database function that can be used on ticket updates and such.
function removeAlert($database, $message, $ticket_id) {
    // Prepare the SQL statement to check for alerts on this ticket
    $alert_stmt = mysqli_prepare($database, "SELECT * FROM alerts WHERE message = ? AND ticket_id = ?");

    // Bind the parameters
    mysqli_stmt_bind_param($alert_stmt, "si", $message, $ticket_id);

    // Execute the statement
    mysqli_stmt_execute($alert_stmt);

    // Get the result
    $result = mysqli_stmt_get_result($alert_stmt);

    // Check if the alert exists
    if (mysqli_num_rows($result) > 0) {
        // The alert exists, delete it
        $delete_stmt = mysqli_prepare($database, "DELETE FROM alerts WHERE message = ? AND ticket_id = ?");
        mysqli_stmt_bind_param($delete_stmt, "si", $message, $ticket_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);
    }
    mysqli_stmt_close($alert_stmt);
}

function get_ticket_notes($ticket_id, $limit) {
    global $database;

    $note_stmt = $database->prepare("SELECT * FROM notes WHERE linked_id = ? ORDER BY created DESC LIMIT ?");
    $note_stmt->bind_param("ii", $ticket_id, $limit);
    $note_stmt->execute();

    $result = $note_stmt->get_result();
    $notes = $result->fetch_all(MYSQLI_ASSOC);

    $note_stmt->close();
    
    // Josh commented out to fix erroring and dying when trying to email from ticket. on 2-5-24
    // Work being done on this issue when things started happening https://github.com/Provo-City-School-District/help.provo.edu/issues/78
    // $database->close();

    return $notes;
}