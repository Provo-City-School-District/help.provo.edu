<?php
require_once("email_utils.php");
require_once("template.php");

// DB connection can fail if not included first, TODO fix maybe

function session_logged_in()
{
    // Ensure $_SESSION is set and is an array
    if (!isset($_SESSION) || !is_array($_SESSION)) {
        return false;
    }
    return array_key_exists("username", $_SESSION) && isset($_SESSION["username"]);
}

function session_is_tech()
{
    // Ensure $_SESSION is set and is an array
    if (!isset($_SESSION) || !is_array($_SESSION)) {
        return false;
    }
    return isset($_SESSION["permissions"]["is_tech"]) && $_SESSION["permissions"]["is_tech"] != 0;
}

function session_is_admin()
{
    return isset($_SESSION["permissions"]["is_admin"]) && $_SESSION["permissions"]["is_admin"] != 0;
}

function session_is_intern()
{
    return isset($_SESSION["permissions"]["is_intern"]) && $_SESSION["permissions"]["is_intern"] != 0;
}

function session_is_supervisor()
{
    return isset($_SESSION["permissions"]["is_supervisor"]) && $_SESSION["permissions"]["is_supervisor"] != 0;
}

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
    $exclude_result = HelpDB::get()->execute_query("SELECT COUNT(*) FROM exclude_days WHERE exclude_day BETWEEN ? AND ?", [$start_date, $end_date]);
    $count = mysqli_fetch_array($exclude_result)[0];
    return $count;
}

// Function to check if a date falls on a weekend
function isWeekend($date)
{
    $dayOfWeek = $date->format('N');
    return ($dayOfWeek == 6 || $dayOfWeek == 7);
}

function isExcludedDate($date)
{
    $exclude_result = HelpDB::get()->execute_query("SELECT COUNT(*) as count FROM exclude_days WHERE exclude_day = ?", [$date]);
    $row = $exclude_result->fetch_assoc();

    if ($row['count'] > 0) {
        return true;
    } else {
        return false;
    }
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

function create_note(
    string $ticket_id,
    string $username,
    string $note_content,
    int $work_hours,
    int $work_minutes,
    int $travel_hours,
    int $travel_minutes,
    bool $visible_to_client,
    ?int $department_id = null,
    string $date_override = null,
    string $email_msg_id = null,
) {
    $ticket_id_clean = trim(htmlspecialchars($ticket_id));
    $note_content_clean = htmlspecialchars(trim($note_content), ENT_QUOTES, 'UTF-8');
    $username_clean = trim(htmlspecialchars($username));
    $work_hours_clean = trim(htmlspecialchars($work_hours));
    $work_minutes_clean = trim(htmlspecialchars($work_minutes));
    $travel_hours_clean = trim(htmlspecialchars($travel_hours));
    $travel_minutes_clean = trim(htmlspecialchars($travel_minutes));
    $department_id_clean = $department_id !== null ? intval($department_id) : null;

    $timestamp = date('Y-m-d H:i:s');


    if (!isset($work_hours) || $work_hours === null || !isset($work_minutes) || $work_minutes === null || !isset($travel_hours) || $travel_hours === null || !isset($travel_minutes) || $travel_minutes === null) {
        return false;
    }

    if ($work_hours < 0 || $work_minutes < 0 || $travel_hours < 0 || $travel_minutes < 0) {
        return false;
    }

    $visible_to_client_intval = intval($visible_to_client);

    // Insert the new note into the database
    $query = "INSERT INTO notes (linked_id, created, creator, note, work_hours, work_minutes, travel_hours, travel_minutes, visible_to_client, date_override, email_msg_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?,?,?)";
    $insert_stmt = mysqli_prepare(HelpDB::get(), $query);
    mysqli_stmt_bind_param(
        $insert_stmt,
        "isssiiiiiss",
        $ticket_id_clean,
        $timestamp,
        $username_clean,
        $note_content_clean,
        $work_hours_clean,
        $work_minutes_clean,
        $travel_hours_clean,
        $travel_minutes_clean,
        $visible_to_client_intval,
        $date_override,
        $email_msg_id
    );
    mysqli_stmt_execute($insert_stmt);
    mysqli_stmt_close($insert_stmt);



    // Log the creation of the new note in the ticket_logs table
    $log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at, department_id, visible_to_client) VALUES (?, ?, ?, NULL, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'), ?, ?)";
    $log_stmt = mysqli_prepare(HelpDB::get(), $log_query);

    $notecolumn = "note";
    mysqli_stmt_bind_param($log_stmt, "isssii", $ticket_id, $username, $notecolumn, $note_content_clean, $department_id_clean, $visible_to_client);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);

    // Send email to assigned tech on update if the client updates ticket
    $client = client_for_ticket($ticket_id_clean);
    $assigned_tech = assigned_tech_for_ticket($ticket_id_clean) ?? 'unassigned';
    $cc_emails = explode(',', emails_for_ticket($ticket_id_clean, false));
    $bcc_emails = explode(',', emails_for_ticket($ticket_id_clean, true));
    $user_email = email_address_from_username(strtolower($username));


    // Mark ticket as unread for assigned tech if anyone else puts in a note
    if ($username != $assigned_tech && user_exists_locally($assigned_tech)) {
        mark_ticket_unread($assigned_tech, $ticket_id_clean);
    }

    log_app(LOG_INFO, $client);

    // Mark ticket as unread for client if anyone else puts in a note
    if ($username != $client && user_exists_locally($client)) {
        mark_ticket_unread($client, $ticket_id_clean);
    }


    // Mark ticket as unread for any CC users that are in the system if anyone else puts in a note
    foreach ($cc_emails as $cc_email) {
        $data = get_info_from_email($cc_email);
        $email_user = $data["user"];
        $email_domain = $data["domain"];

        // non provo.edu can never be in the system, so skip
        if ($email_domain != 'provo.edu')
            continue;

        // if not in system, skip
        if (!user_exists_locally($email_user))
            continue;

        // no need to clear ticket status for ourselves (we put in the note)
        if ($email_user == $username)
            continue;

        mark_ticket_unread($email_user, $ticket_id);
    }


    // Mark ticket as unread for any BCC users that are in the system if anyone else puts in a note
    foreach ($bcc_emails as $bcc_email) {
        $data = get_info_from_email($bcc_email);
        $email_user = $data["user"];
        $email_domain = $data["domain"];

        // non provo.edu can never be in the system, so skip
        if ($email_domain != 'provo.edu')
            continue;

        // if not in system, skip
        if (!user_exists_locally($email_user))
            continue;

        // no need to clear ticket status for ourselves (we put in the note)
        if ($email_user == $username)
            continue;

        mark_ticket_unread($email_user, $ticket_id);
    }

    // Allow pseudo-clients to update ticket status if in CC/BCC field
    if ((strtolower($username) == strtolower($client) ||
            in_array($user_email, $cc_emails) ||
            in_array($user_email, $bcc_emails)) && !user_is_tech($username) ||
        client_for_ticket($ticket_id_clean) == $username
    ) {
        // set priority to standard
        $result = HelpDB::get()->execute_query("UPDATE tickets SET tickets.priority = 10 WHERE tickets.id = ?", [$ticket_id_clean]);
        if (!$result) {
            log_app(LOG_ERR, "Failed to update ticket priority for id=$operating_ticket");
            return false;
        }

        if (status_for_ticket($ticket_id_clean) == "resolved") {
            $result = HelpDB::get()->execute_query("UPDATE tickets SET tickets.status = 'open' WHERE tickets.id = ?", [$ticket_id_clean]);
            if (!$result) {
                log_app(LOG_ERR, "Failed to update ticket status for id=$operating_ticket");
                return false;
            }
        }

        $client_name = get_client_name(client_for_ticket($ticket_id_clean));
        $location_name = location_name_from_id(location_for_ticket($ticket_id_clean));
        $ticket_desc = description_for_ticket($ticket_id_clean);

        // Get the last 3 notes for the ticket
        $notes = get_ticket_notes($ticket_id_clean, 3);
        $notes_message_tech = "";

        if (count($notes) > 0) {
            $notes_message_tech .= "<tr><th>Date</th><th>Creator</th><th>Note</th></tr>";
        }

        foreach ($notes as $note) {
            $date_override = $note['date_override'];
            $effective_date = $date_override;
            if ($date_override == null)
                $effective_date = $note['created'];

            $date_str = date_format(date_create($effective_date), "F jS\, Y h:i:s A");
            $note_creator = $note['creator'];
            $decoded_note = htmlspecialchars_decode($note['note']);

            $note_theme = "";
            if (!user_is_tech($note_creator)) {
                $note_theme = "nonTech";
            } else if ($note['visible_to_client'] == 0) {
                $note_theme = "notClientVisible";
            } else {
                $note_theme = "clientVisible";
            }

            $notes_message_tech .= "<tr><td>$date_str</td><td>$note_creator</td><td><span class=\"$note_theme\">$decoded_note</span></td></tr>";
        }


        // Email tech if client has updated ticket
        $email_subject = "Ticket $ticket_id_clean (Updated)";
        $template_tech = new Template(from_root("/includes/templates/ticket_updated_tech.phtml"));

        $template_tech->client = $client_name["firstname"] . " " . $client_name["lastname"];
        $template_tech->location = $location_name;
        $template_tech->ticket_id = $ticket_id_clean;
        $template_tech->changes_message = "$username added a note";
        $template_tech->notes_message = $notes_message_tech;
        $template_tech->site_url = getenv('ROOTDOMAIN');
        $template_tech->description = html_entity_decode($ticket_desc);
        $template_tech->room = field_for_ticket($ticket_id_clean, "room") ?: "<empty>";
        $template_tech->phone = field_for_ticket($ticket_id_clean, "phone") ?: "<empty>";

        $result = HelpDB::get()->execute_query(
            "SELECT attachment_path from help.tickets WHERE id = ?",
            [$ticket_id_clean]
        );
        if (!$result) {
            log_app(LOG_ERR, "Failed to get old attachment_path");
        }

        $attachment_data = $result->fetch_assoc();

        $all_attachment_paths = explode(',', $attachment_data["attachment_path"]);
        $attachment_paths = [];
        $attachment_urls = [];

        foreach ($all_attachment_paths as $path) {
            $real_path = realpath(from_root("/../uploads/$path"));
            $file_size = filesize($real_path);

            if ($file_size >= get_max_attachment_file_size()) {
                $root = getenv('ROOTDOMAIN');
                $filename = basename($path);
                $url = "$root/upload_viewer.php?file=$filename";
                $attachment_urls[] = ["url" => $url, "filename" => $filename];
            } else {
                $attachment_paths[] = $path;
            }
        }

        $remaining_tasks_query = "SELECT assigned_tech, description FROM ticket_tasks WHERE (completed != 1 AND ticket_id = ?)";

        $remaining_tasks_result = HelpDB::get()->execute_query($remaining_tasks_query, [$ticket_id_clean]);
        $remaining_tasks = [];

        while ($row = $remaining_tasks_result->fetch_assoc()) {
            $tech_name = null;
            $assigned_tech = $row["assigned_tech"];
            if (isset($assigned_tech)) {
                $tech_name = get_local_name_for_user($assigned_tech);
            }

            $tech = "Unassigned";
            if ($tech_name != null) {
                $tech = $tech_name["firstname"] . " " . $tech_name["lastname"];
            }
            $desc = $row["description"];
            $remaining_tasks[] =  ["tech_name" => $tech, "description" => $desc];
        }

        $template_tech->remaining_tasks = $remaining_tasks;
        $template_tech->attachment_urls = $attachment_urls;

        // Skip email to tech if ticket is still unassigned
        if ($assigned_tech !== null) {
            log_app(LOG_INFO, "Emailing assigned tech $assigned_tech that client is updating ticket");
            send_email_and_add_to_ticket($ticket_id_clean, email_address_from_username($assigned_tech), $email_subject, $template_tech, [], [], $attachment_paths);
        }
    }
    return true;
}

/*
ticket_params should be an associative array with these values set:
REQUIRED:
    client (string) => the ticket client 
    title (string) => the ticket title
    desc (string) => the ticket description / request body
OPTIONAL:
    priority (int) => ticket priority, default=10
    location (int) => location code, default=null
    department (int) => department, default=1897 (tech dept)
    email_msg_id (string) => associate the ticket with an email message id 
        such that replies to this email get inserted as notes
    
RETURNS:
    created ticket_id or 0 if failed
*/

function __create_ticket(array $ticket_params)
{
    $client = $ticket_params['client'];
    $subject = $ticket_params['title'];
    $content = $ticket_params['desc'];
    if (!isset($client) || !isset($subject) || !isset($content)) {
        // can't create ticket, return 0
        return 0;
    }

    $priority = $ticket_params['priority'] ?? 10;
    $location_code = $ticket_params['location'] ?? null;
    $department_code = $ticket_params['department'] ?? 1897;
    $email_msg_id = $ticket_params['email_msg_id'] ?? null;


    $client_clean = trim(htmlspecialchars($client));
    $subject_clean = limitChars(trim(htmlspecialchars($subject)), 100);
    $content_clean = trim(htmlspecialchars($content));

    // Create an SQL INSERT query
    $insert_query = "INSERT INTO tickets (location, room, name, description, created, last_updated, due_date, status, client,attachment_path,phone,cc_emails,bcc_emails,request_type_id,priority,department)
                VALUES (?, NULL, ?, ?, ?, ?, ?, 'open', ?, '', '', '', '', 0, 10, ?)";

    // Prepare the SQL statement
    $create_stmt = mysqli_prepare(HelpDB::get(), $insert_query);

    if ($create_stmt === false) {
        log_app(LOG_ERR, 'Error preparing insert query: ' . mysqli_error(HelpDB::get()));
        return 0;
    }

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
        $create_stmt,
        'issssssi',
        $location_code,
        $subject_clean,
        $content_clean,
        $created_time,
        $created_time,
        $due_date,
        $client_clean,
        $department_code
    );


    // Execute the prepared statement
    if (mysqli_stmt_execute($create_stmt)) {
        log_app(LOG_INFO, "create_ticket success");

        $created_ticket_id = mysqli_insert_id(HelpDB::get());
        if (isset($email_msg_id)) {
            add_ticket_msg_id_mapping($email_msg_id, $created_ticket_id);
        }

        // add attachments
        if (isset($ticket_params['attachments'])) {
            $files = $ticket_params['attachments'];
            if (!empty($files)) {
                handleFileUploads($files, $created_ticket_id);
            }
        }

        return $created_ticket_id;
    } else {
        log_app(LOG_ERR, "create_ticket failure");
        return 0;
    }
}

// Returns true on success, false on failure
function create_ticket(
    string $client, 
    string $subject, 
    string $content, 
    string $email_msg_id, 
    int $location_code, 
    int &$created_ticket_id)
{
    $params = [
        'client' => $client,
        'title' => $subject,
        'desc' => $content,
        'location' => $location_code,
        'email_msg_id' => $email_msg_id
    ];
    $created_ticket_id = __create_ticket($params);
    return $created_ticket_id != 0;
}

// Messages for alerts
$alert48Message = "Ticket hasn't been updated in 48 hours";
$alert7DayMessage = "Ticket hasn't been updated in 7 days";
$alert15DayMessage = "Ticket hasn't been updated in 15 days";
$alert20DayMessage = "Ticket hasn't been updated in 20 days";
$pastDueMessage = "Past Due";

//remove alerts from the database function that can be used on ticket updates and such.
function removeAlert($database, $message, $ticket_id)
{
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
function removeAllAlertsByTicketId($ticket_id)
{
    $deleteAlertsQuery = HelpDB::get()->execute_query("DELETE FROM alerts WHERE ticket_id = ?", [$ticket_id]);
    return $deleteAlertsQuery;
}
function request_name_for_type($request_type)
{

    if ($request_type === '0') {
        return "Other";
    } else {
        $request_type_query_result = HelpDB::get()->execute_query("SELECT request_name FROM request_type WHERE request_id = ?", [$request_type]);
        return mysqli_fetch_assoc($request_type_query_result)['request_name'];
    }
}

function get_ticket_notes($ticket_id, $limit)
{

    $note_stmt = HelpDB::get()->prepare("SELECT * FROM notes WHERE linked_id = ? ORDER BY created DESC LIMIT ?");
    $note_stmt->bind_param("ii", $ticket_id, $limit);
    $note_stmt->execute();

    $result = $note_stmt->get_result();
    $notes = $result->fetch_all(MYSQLI_ASSOC);

    $note_stmt->close();
    // Josh commented out to fix erroring and dying when trying to email from ticket. on 2-5-24
    // Work being done on this issue when things started happening https://github.com/Provo-City-School-District/help.provo.edu/issues/78
    // HelpDB::get()->close();

    return $notes;
}

function displayTime($note, $type)
{
    $hours = $note[$type . '_hours'];
    $minutes = $note[$type . '_minutes'];

    if ((isset($hours) && $hours > 0) || (isset($minutes) && $minutes > 0)) {
        echo "<p><strong>" . ucfirst($type) . " Time</strong></p>";
        if (isset($hours)) {
            if ($hours == 1)
                echo $hours . " hour ";
            else if ($hours > 1)
                echo $hours . " hours ";
        }
        if (isset($minutes)) {
            if ($minutes == 1)
                echo $minutes . " minute";
            else if ($minutes > 1)
                echo $minutes . " minutes";
        }
    }
}
function displayTotalTime($total_hours, $total_minutes)
{
    // Convert minutes to hours and minutes
    $total_minutes = $total_hours * 60 + $total_minutes;
    $hours = floor($total_minutes / 60);
    $minutes = $total_minutes % 60;

    if ($hours > 0 || $minutes > 0) {
        echo "<span><strong>Total Time</strong></span>: ";
        if ($hours > 0) {
            echo $hours . ($hours == 1 ? " hour " : " hours ");
        }
        if ($minutes > 0) {
            echo $minutes . ($minutes == 1 ? " minute" : " minutes");
        }
    }
}
// TODO: similar function in search_tickets.php. lets consolidate
function location_name_from_id(?string $site_id): string
{
    if ($site_id === null || $site_id === "") {
        return "Unknown";
    }

    $location_result = HelpDB::get()->execute_query("SELECT location_name FROM help.locations WHERE sitenumber = ?", [$site_id]);
    if (!$location_result) {
        log_app(LOG_ERR, "[location_name_from_id] Failed to get location query result");
        return "Unknown";
    }

    $location_data = mysqli_fetch_assoc($location_result);
    if (!$location_data) {
        log_app(LOG_ERR, "[location_name_from_id] Failed to get location data for id $site_id");
        return "Site " . $site_id;
    }

    return $location_data["location_name"];
}

function assigned_tech_for_ticket(int $ticket_id)
{

    $assigned_result = HelpDB::get()->execute_query("SELECT employee FROM help.tickets WHERE tickets.id = ?", [$ticket_id]);
    if (!isset($assigned_result)) {
        log_app(LOG_ERR, "[assigned_tech_for_ticket] Failed to get location query result");
    }

    $assigned_data = mysqli_fetch_assoc($assigned_result);
    if (!isset($assigned_data)) {
        log_app(LOG_ERR, "[assigned_tech_for_ticket] Failed to get location data");
    }

    return $assigned_data["employee"];
}

function client_for_ticket(int $ticket_id)
{

    $client_result = HelpDB::get()->execute_query("SELECT client FROM help.tickets WHERE tickets.id = ?", [$ticket_id]);
    if (!isset($client_result)) {
        log_app(LOG_ERR, "[client_for_ticket] Failed to get location query result");
    }

    $client_data = mysqli_fetch_assoc($client_result);
    if (!isset($client_data)) {
        log_app(LOG_ERR, "[client_for_ticket] Failed to get location data");
    }

    return strtolower($client_data["client"]);
}

/*
emails_for_ticket
Input: ticket_id, flag for whether this should return bcc or just cc
Output: The result set of cc or bcc emails on the ticket
*/
function emails_for_ticket(int $ticket_id, bool $bcc)
{

    if ($bcc) {
        $email_type = "bcc_emails";
        $query = "SELECT bcc_emails FROM help.tickets WHERE tickets.id = ?";
    } else {
        $email_type = "cc_emails";
        $query = "SELECT cc_emails FROM help.tickets WHERE tickets.id = ?";
    }

    $email_result = HelpDB::get()->execute_query($query, [$ticket_id]);
    if (!isset($email_result)) {
        log_app(LOG_ERR, "[emails_for_ticket] Failed to get result");
    }

    $email_data = $email_result->fetch_assoc();
    if (!isset($email_data)) {
        log_app(LOG_ERR, "[emails_for_ticket] Failed to get data");
    }

    return $email_data[$email_type];
}

function status_for_ticket(int $ticket_id)
{

    $status_result = HelpDB::get()->execute_query("SELECT status FROM help.tickets WHERE tickets.id = ?", [$ticket_id]);
    if (!isset($status_result)) {
        log_app(LOG_ERR, "[status_for_ticket] Failed to get status query result");
    }

    $status_data = mysqli_fetch_assoc($status_result);
    if (!isset($status_data)) {
        log_app(LOG_ERR, "[status_for_ticket] Failed to get status data");
    }

    return $status_data["status"];
}

function location_for_ticket(int $ticket_id)
{

    $loc_result = HelpDB::get()->execute_query("SELECT location FROM help.tickets WHERE tickets.id = ?", [$ticket_id]);
    if (!isset($loc_result)) {
        log_app(LOG_ERR, "[location_for_ticket] Failed to get location query result");
    }

    $loc_data = mysqli_fetch_assoc($loc_result);
    if (!isset($loc_data)) {
        log_app(LOG_ERR, "[location_for_ticket] Failed to get location data");
    }

    return $loc_data["location"];
}

function description_for_ticket(int $ticket_id)
{

    $desc_result = HelpDB::get()->execute_query("SELECT description FROM help.tickets WHERE tickets.id = ?", [$ticket_id]);
    if (!isset($desc_result)) {
        log_app(LOG_ERR, "[location_for_ticket] Failed to get description query result");
    }

    $desc_data = mysqli_fetch_assoc($desc_result);
    if (!isset($desc_data)) {
        log_app(LOG_ERR, "[location_for_ticket] Failed to get description data");
    }

    return $desc_data["description"];
}

function field_for_ticket(int $ticket_id, string $field)
{
    // add to this later
    $allowed_fields = ["room", "phone", "cc_emails", "bcc_emails"];
    if (!in_array($field, $allowed_fields, true)) {
        return null;
    }

    // unsafe to drop string directly in the query but with strict validation above we should be good
    $result = HelpDB::get()->execute_query("SELECT $field FROM help.tickets WHERE tickets.id = ?", [$ticket_id]);
    if (!isset($result)) {
        log_app(LOG_ERR, "[field_for_ticket] Failed to get $field query result");
        return null;
    }

    $data = $result->fetch_assoc();
    if (!isset($data)) {
        log_app(LOG_ERR, "[field_for_ticket] Failed to get $field data");
        return null;
    }

    return $data[$field];
}

function logTicketChange($database, $ticket_id, $updatedby, $field_name, $old_value, $new_value)
{
    $log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at) VALUES (?, ?, ?, ?, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))";
    $log_stmt = mysqli_prepare($database, $log_query);
    mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $field_name, $old_value, $new_value);
    mysqli_stmt_execute($log_stmt);
}

function generateUpdateHTML($type, $old_value, $new_value, $action, $id)
{
    $old_value = $old_value != null ? html_entity_decode($old_value) . ' To: ' : '';
    $new_value = $new_value != null ? html_entity_decode($new_value) : '';

    return '<div class="note-container" id="note-container-' . $id . '">' . $type . ' ' . $action . ': <span class="note" id="note-' . $id . '">' . $old_value . $new_value . '</span></div>';
}

function get_parsed_ticket_data($ticket_data)
{

    $priorityTypes = [1 => "Critical", 3 => "Urgent", 5 => "High", 10 => "Standard", 15 => "Client Response", 30 => "Project", 60 => "Meeting Support"];

    $tickets = [];
    while ($row = $ticket_data->fetch_assoc()) {
        $tmp = [];
        $tmp["id"] = $row["id"];
        // Handle missing latest_note_author
        $tmp["latest_note_author"] = $row["latest_note_author"] ?? "Unknown";

        if (isset($row['alert_levels'])) {
            $alerts_split = explode(',', $row['alert_levels']);
            $tmp["red_alert_enabled"] = in_array('crit', $alerts_split);
            $tmp["yellow_alert_enabled"] = in_array('warn', $alerts_split);
            $tmp["task_alert_enabled"] = in_array('Task', $alerts_split);
        } else {
            $tmp["red_alert_enabled"] = false;
            $tmp["yellow_alert_enabled"] = false;
            $tmp["task_alert_enabled"] = false;
        }

        $tmp["title"] = $row["name"];
        $tmp["description"] = limitChars(strip_tags(html_entity_decode($row["description"])), 100);

        if (isset($row["latest_note_author"]) && session_is_tech() && are_users_in_same_department($row["latest_note_author"], $_SESSION["username"])) {
            $notes_query = "SELECT creator, note FROM help.notes WHERE linked_id = ? ORDER BY
                (CASE WHEN date_override IS NULL THEN created ELSE date_override END) DESC
            ";
        } else {
            $notes_query = "SELECT creator, note FROM help.notes WHERE (linked_id = ? AND visible_to_client = 1) ORDER BY
            (CASE WHEN date_override IS NULL THEN created ELSE date_override END) DESC";
        }
        $notes_stmt_result = HelpDB::get()->execute_query($notes_query, [$row["id"]]);
        $notes_stmt_data = $notes_stmt_result->fetch_assoc();

        $latest_note_str = "";
        if (
            isset($notes_stmt_data) && array_key_exists("creator", $notes_stmt_data) &&
            array_key_exists("note", $notes_stmt_data)
        ) {
            $tmp["latest_note_author"] = $notes_stmt_data["creator"];
            $tmp["latest_note"] = limitChars(strip_tags(html_entity_decode($notes_stmt_data["note"])), 150);
        }

        $tmp["client_username"] = $row["client"];
        if (isset($row["client"])) {
            $result = get_client_name($row["client"]);
        }

        $tmp["client_first_name"] = $result['firstname'] ?: "";
        $tmp["client_last_name"] = $result['lastname'] ?: "";

        $location_query = "SELECT location_name FROM locations WHERE sitenumber = ?";
        $loc_stmt = mysqli_prepare(HelpDB::get(), $location_query);
        $location_name = "";
        if ($loc_stmt) {
            mysqli_stmt_bind_param($loc_stmt, "s", $row["location"]);
            mysqli_stmt_execute($loc_stmt);
            mysqli_stmt_bind_result($loc_stmt, $location_name);

            // Fetch the result
            mysqli_stmt_fetch($loc_stmt);

            // Use $location_name as needed
            mysqli_stmt_close($loc_stmt);
        }

        $tmp["room"] = $row["room"];
        $tmp["location_name"] = $location_name;

        if ($row['request_type_id'] === '0') {
            $request_type_name = "Other";
        } else {
            $request_type_query_result = HelpDB::get()->execute_query("SELECT request_name FROM request_type WHERE request_id = ?", [$row['request_type_id']]);
            $request_type_name = mysqli_fetch_assoc($request_type_query_result)['request_name'];
        }

        $tmp["request_category"] = $request_type_name;
        $tmp["status"] = $row["status"];

        $priority = $row["priority"];
        $tmp["priority"] = $priorityTypes[$priority];
        $tmp["sort_value"] = $priority;
        $tmp["created"] = $row["created"];
        $tmp["last_updated"] = $row["last_updated"];
        $tmp["due_date"] = $row["due_date"];
        if ($row["employee"] == null) {
            $tmp["assigned_tech"] = "Unassigned";
        } else {
            $tmp["assigned_tech"] = $row["employee"];
        }
        $tmp["alert_level"] = isset($row["alert_levels"]) ? $row["alert_levels"] : '';

        $last_viewed_query = <<<STR
            SELECT last_viewed FROM ticket_viewed WHERE user_id = ? AND ticket_id = ?
        STR;
        $user_id = get_id_for_user($_SESSION["username"]);
        $last_viewed_res = HelpDB::get()->execute_query($last_viewed_query, [$user_id, $row["id"]]);
        // echo var_dump($last_viewed_res);
        $tmp["blue_alert_enabled"] = !isset($last_viewed_res->fetch_assoc()["last_viewed"]);
        $tickets[] = $tmp;
    }
    return $tickets;
}

function get_parsed_alert_data($alerts_result)
{
    $data = [];
    while ($row = $alerts_result->fetch_assoc()) {
        $tmp = [];
        $tmp["alert_level"] = $row["alert_level"];
        $tmp["ticket_id"] = $row["ticket_id"];
        $tmp["message"] = $row["message"];
        $tmp["employee"] = $row["employee"];
        $tmp["id"] = $row["id"];
        $data[] = $tmp;
    }
    return $data;
}

/**
 * Sanitizes ldap search strings.
 * See rfc2254
 * @link http://www.faqs.org/rfcs/rfc2254.html
 * @since 1.5.1 and 1.4.5
 * @param string $string
 * @return string sanitized string
 * @author Squirrelmail Team
 */
function ldapspecialchars($string)
{
    $sanitized = array(
        '\\' => '\5c',
        '*' => '\2a',
        '(' => '\28',
        ')' => '\29',
        "\x00" => '\00'
    );

    return str_replace(array_keys($sanitized), array_values($sanitized), $string);
}

function get_tech_usernames($department = null)
{
    $query = "
        SELECT u.username, us.is_tech 
        FROM users u
        LEFT JOIN user_settings us ON u.id = us.user_id
        WHERE us.is_tech = 1
    ";

    if ($department !== null) {
        $query .= " AND us.department = ?";
        $usernamesResult = HelpDB::get()->execute_query($query, [$department]);
    } else {
        $query .= " ORDER BY u.username ASC";
        $usernamesResult = HelpDB::get()->execute_query($query);
    }

    if (!$usernamesResult) {
        log_app(LOG_ERR, "[get_tech_usernames] Failed to query database");
        return [];
    }

    // Store the usernames in an array
    $tech_usernames = [];
    while ($usernameRow = mysqli_fetch_assoc($usernamesResult)) {
        if ($usernameRow['is_tech']) {
            $tech_usernames[] = $usernameRow['username'];
        }
    }
    return $tech_usernames;
}
function getPriorityName(int $priority)
{
    switch ($priority) {
        case 1:
            return "Critical";
        case 3:
            return "Urgent";
        case 5:
            return "High";
        case 10:
            return "Standard";
        case 15:
            return "Client Response";
        case 30:
            return "Project";
        case 60:
            return "Meeting Support";
        default:
            return "Unknown";
    }
}

function get_child_tickets_for_ticket(int $ticket_id)
{
    $res = HelpDB::get()->execute_query("SELECT id FROM tickets WHERE parent_ticket = ?", [$ticket_id]);
    $ids = [];

    while ($row = $res->fetch_assoc()) {
        $ids[] = $row["id"];
    }

    return $ids;
}

function get_parent_ticket_for_ticket(int $ticket_id)
{
    $res = HelpDB::get()->execute_query("SELECT parent_ticket FROM tickets WHERE id = ?", [$ticket_id]);
    $row = $res->fetch_assoc();
    return $row["parent_ticket"];
}

function get_user_setting($userId, $settingName)
{
    try {
        // query to get the setting value
        $result = HelpDB::get()->execute_query("SELECT $settingName FROM user_settings WHERE user_id = ?", [$userId]);

        // Fetch the setting value if it exists
        if ($result && $row = $result->fetch_assoc()) {
            return $row[$settingName];
        }

        // Return null if the setting is not found
        return null;
    } catch (Exception $e) {
        // if setting is not found, log the error and return null
        log_app(LOG_ERR, "Failed to get user setting: $settingName for user: $userId");
        return null;
    }
}



function get_id_for_user(string $username)
{
    $user_id_res = HelpDB::get()->execute_query("SELECT id FROM help.users WHERE username = ?", [$username]);
    return $user_id_res->fetch_assoc()["id"];
}

function mark_ticket_unread(string $username, int $ticket_id)
{
    $user_id = get_id_for_user($username);
    log_app(LOG_INFO, $user_id);

    $remove_read_query = "DELETE FROM ticket_viewed WHERE (user_id = ? AND ticket_id = ?)";
    $remove_read_res = HelpDB::get()->execute_query($remove_read_query, [$user_id, $ticket_id]);

    return (bool)$remove_read_res;
}

function user_exists_locally(string $username)
{

    $check_query = "SELECT * FROM users WHERE username = ?";
    $result = HelpDB::get()->execute_query($check_query, [$username]);

    // If a row is returned, the user exists
    return mysqli_num_rows($result) > 0;
}

function set_field_for_ticket(int $ticket_id, string $field, $value)
{
    // Add to this later
    $allowed_fields = ["employee", "status", "department"];
    if (!in_array($field, $allowed_fields, true)) {
        return false;
    }

    // Fetch the current value of the field
    $current_value_result = HelpDB::get()->execute_query("SELECT $field FROM tickets WHERE id = ?", [$ticket_id]);
    if (!$current_value_result) {
        log_app(LOG_ERR, "Failed to fetch current value of field \"$field\" for ticket id=$ticket_id");
        return false;
    }

    $current_value = $current_value_result->fetch_assoc()[$field] ?? null;

    // Update the field in the database
    $result = HelpDB::get()->execute_query("UPDATE tickets SET $field = ? WHERE id = ?", [$value, $ticket_id]);
    if (!$result) {
        log_app(LOG_ERR, "Failed to update ticket field \"$field\" for id=$ticket_id");
        return false;
    }

    // Log the change
    $updated_by = $_SESSION['username'] ?? 'system';
    logTicketChange(HelpDB::get(), $ticket_id, $updated_by, $field, $current_value, $value);

    return true;
}

function get_departments()
{
    // Query the locations table to get the departments
    $department_query = "SELECT sitenumber, location_name FROM locations WHERE is_department = TRUE ORDER BY location_name ASC";
    $department_result = HelpDB::get()->execute_query($department_query);

    $tmp = [];
    while ($row = $department_result->fetch_assoc()) {
        $tmp[] = $row;
    }

    return $tmp;
}

function get_sitenumber_from_location_id($department)
{
    $sitenumber_query = "SELECT sitenumber FROM locations WHERE location_id = ?";
    $sitenumber_result = HelpDB::get()->execute_query($sitenumber_query, [$department]);
    $sitenumber_row = mysqli_fetch_assoc($sitenumber_result);
    return $sitenumber_row['sitenumber'] ?? null;
}
// get users department
function get_user_department($username)
{
    // Query to fetch the department of the user
    $query = "SELECT us.department FROM users u INNER JOIN user_settings us ON u.id = us.user_id WHERE u.username = ?";
    $result = HelpDB::get()->execute_query($query, [$username]);

    if (!$result) {
        log_app(LOG_ERR, "Failed to fetch department for user: $username");
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    return $row['department'] ?? null;
}
function get_user_department_name($department)
{
    // Query to fetch the department name
    $query = "SELECT location_name FROM locations WHERE location_id = ?";
    $result = HelpDB::get()->execute_query($query, [$department]);

    if (!$result) {
        log_app(LOG_ERR, "Failed to fetch department name for department: $department");
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    return $row['location_name'] ?? null;
}
// Check if two users are in the same department
function are_users_in_same_department($creator_username, $current_username)
{
    // Query to fetch the department of both users
    $query = "SELECT u.username, us.department 
              FROM users u
              INNER JOIN user_settings us ON u.id = us.user_id
              WHERE u.username IN (?, ?)";
    $result = HelpDB::get()->execute_query($query, [$creator_username, $current_username]);

    if (!$result) {
        log_app(LOG_ERR, "Failed to fetch departments for users: $creator_username and $current_username");
        return false;
    }

    $departments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $departments[$row['username']] = $row['department'];
    }

    // Ensure both users were found and compare their departments
    return isset($departments[$creator_username], $departments[$current_username]) &&
        $departments[$creator_username] === $departments[$current_username];
}
// Build dropdown for assigned tech that shows all tech in the department
function render_tech_usernames_dropdown($tech_usernames, $selected = null, $select_name = "assigned_tech", $include_unassigned = true)
{
?>
    <select name="<?= htmlspecialchars($select_name) ?>" id="<?= htmlspecialchars($select_name) ?>">
        <?php if ($include_unassigned): ?>
            <option value="unassigned" <?= ($selected === "unassigned" || $selected === "" || $selected === null) ? 'selected' : '' ?>>Unassigned</option>
        <?php endif; ?>
        <?php foreach ($tech_usernames as $tmp_username) : ?>
            <?php
            $name = get_local_name_for_user($tmp_username);
            $firstname = ucwords(strtolower($name["firstname"]));
            $lastname = ucwords(strtolower($name["lastname"]));
            $display_string = $firstname . " " . $lastname . " - " . location_name_from_id(get_fast_client_location($tmp_username) ?: "");
            ?>
            <option value="<?= $tmp_username ?>" <?= ($selected === $tmp_username) ? 'selected' : '' ?>>
                <?= $display_string ?>
            </option>
        <?php endforeach; ?>
        <?php
        // If the selected tech is not in the dropdown, show them as a disabled selected option
        if (
            !empty($selected) &&
            $selected !== "unassigned" &&
            !in_array($selected, $tech_usernames)
        ) {
            $current_tech_name = get_local_name_for_user($selected);
            if ($current_tech_name) {
                $current_firstname = ucwords(strtolower($current_tech_name["firstname"]));
                $current_lastname = ucwords(strtolower($current_tech_name["lastname"]));
                $current_display_string = $current_firstname . " " . $current_lastname . " - " . location_name_from_id(get_fast_client_location($selected) ?: "");
            } else {
                $current_display_string = 'Assigned outside the department';
            }
        ?>
            <option value="<?= htmlspecialchars($selected) ?>" selected disabled>
                <?= $current_display_string ?> (Current Assigned Tech)
            </option>
        <?php } ?>
    </select>
<?php
}
