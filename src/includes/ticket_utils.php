<?php
require_once("email_utils.php");
// DB connection can fail if not included first, TODO fix maybe

function session_is_tech()
{
    return $_SESSION["permissions"]["is_tech"] != 0;
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
    global $database;
    $exclude_result = $database->execute_query("SELECT COUNT(*) FROM exclude_days WHERE exclude_day BETWEEN ? AND ?", [$start_date, $end_date]);
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
    global $database;
    $exclude_result = $database->query("SELECT COUNT(*) as count FROM exclude_days WHERE exclude_day = '$date'");
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

function add_note_with_filters(
    string $ticket_id,
    string $username,
    string $note_content,
    int $work_hours,
    int $work_minutes,
    int $travel_hours,
    int $travel_minutes,
    bool $visible_to_client,
    string $date_override = null,
    string $email_msg_id = null,
) {
    global $database;

    $ticket_id_clean = trim(htmlspecialchars($ticket_id));
    $note_content_clean = trim(htmlspecialchars($note_content));
    $username_clean = trim(htmlspecialchars($username));
    $work_hours_clean = trim(htmlspecialchars($work_hours));
    $work_minutes_clean = trim(htmlspecialchars($work_minutes));
    $travel_hours_clean = trim(htmlspecialchars($travel_hours));
    $travel_minutes_clean = trim(htmlspecialchars($travel_minutes));
    $timestamp = date('Y-m-d H:i:s');

    if (!isset($work_hours) || $work_hours === null || !isset($work_minutes) || $work_minutes === null || !isset($travel_hours) || $travel_hours === null || !isset($travel_minutes) || $travel_minutes === null) {
        return false;
    }

    $visible_to_client_intval = intval($visible_to_client);

    // Insert the new note into the database
    $query = "INSERT INTO notes (linked_id, created, creator, note, work_hours, work_minutes, travel_hours, travel_minutes, visible_to_client, date_override, email_msg_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?,?,?)";
    $insert_stmt = mysqli_prepare($database, $query);
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
    $log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at) VALUES (?, ?, ?, NULL, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))";
    $log_stmt = mysqli_prepare($database, $log_query);

    $notecolumn = "note";
    mysqli_stmt_bind_param($log_stmt, "isss", $ticket_id, $username, $notecolumn, $note_content_clean);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);

    // Send email to assigned tech on update if the client updates ticket
    $client = client_for_ticket($ticket_id_clean);

	log_app(LOG_INFO, "username: $username, client: $client");

    if ($username == $client) {
		// set priority to standard
        $result = $database->execute_query("UPDATE tickets SET tickets.priority = 10 WHERE tickets.id = ?", [$ticket_id_clean]);
        if (!$result) {
            log_app(LOG_ERR, "Failed to update ticket priority for id=$operating_ticket");
			return false;
        }

		if (status_for_ticket($ticket_id_clean) == "resolved") {
			$result = $database->execute_query("UPDATE tickets SET tickets.status = 'open' WHERE tickets.id = ?", [$ticket_id_clean]);
			if (!$result) {
				log_app(LOG_ERR, "Failed to update ticket status for id=$operating_ticket");
				return false;
			}
		}

        // Email tech if client has updated ticket
        $email_subject = "Ticket $ticket_id_clean (Updated)";
        $email_msg = "Ticket $ticket_id_clean has been updated by the client.\n<a href=\"https://help.provo.edu/controllers/tickets/edit_ticket.php?id=$ticket_id_clean\">View ticket here</a>";
        $assigned_tech = assigned_tech_for_ticket($ticket_id_clean);

        //Skips email to Tech is still unassigned.
        if ($assigned_tech !== null) {
            log_app(LOG_INFO, "Emailing assigned tech $assigned_tech that client is updating ticket");
            send_email_and_add_to_ticket($ticket_id_clean, email_address_from_username($assigned_tech), $email_subject, $email_msg);
        }
    }
    return true;
}

// Returns true on success, false on failure
function create_ticket(string $client, string $subject, string $content, string $email_msg_id, int $location_code, int &$created_ticket_id)
{
    global $database;

    $client_clean = trim(htmlspecialchars($client));
    $subject_clean = limitChars(trim(htmlspecialchars($subject)), 100);
    $content_clean = trim(htmlspecialchars($content));

    // Create an SQL INSERT query
    $insertQuery = "INSERT INTO tickets (location, room, name, description, created, last_updated, due_date, status, client,attachment_path,phone,cc_emails,bcc_emails,request_type_id,priority)
                VALUES (?, NULL, ?, ?, ?, ?, ?, 'open', ?, '', '', '', '', 0, 10)";

    // Prepare the SQL statement
    $create_stmt = mysqli_prepare($database, $insertQuery);

    if ($create_stmt === false) {
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
        $create_stmt,
        'issssss',
        $location_code,
        $subject_clean,
        $content_clean,
        $created_time,
        $created_time,
        $due_date,
        $client_clean,
    );


    // Execute the prepared statement
    if (mysqli_stmt_execute($create_stmt)) {
        log_app(LOG_INFO, "create_ticket success");

        $created_ticket_id = mysqli_insert_id($database);
        add_ticket_msg_id_mapping($email_msg_id, $created_ticket_id);
        return true;
    } else {
        log_app(LOG_ERR, "create_ticket failure");
        return false;
    }

    mysqli_stmt_close($create_stmt);
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

function get_ticket_notes($ticket_id, $limit)
{
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
function location_name_from_id(string $site_id)
{
    if ($site_id == "")
        return "Unknown";

    global $database;

    $location_result = $database->execute_query("SELECT location_name FROM help.locations WHERE sitenumber = ?", [$site_id]);
    if (!isset($location_result)) {
        log_app(LOG_ERR, "[location_name_from_id] Failed to get location query result");
    }

    $location_data = mysqli_fetch_assoc($location_result);
    if (!isset($location_data)) {
        log_app(LOG_ERR, "[location_name_from_id] Failed to get location data for id $site_id");
        return "Site " . $site_id;
    }

    return $location_data["location_name"];
}

function assigned_tech_for_ticket(int $ticket_id)
{
    global $database;

    $assigned_result = $database->execute_query("SELECT employee FROM help.tickets WHERE tickets.id = ?", [$ticket_id]);
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
    global $database;

    $client_result = $database->execute_query("SELECT client FROM help.tickets WHERE tickets.id = ?", [$ticket_id]);
    if (!isset($client_result)) {
        log_app(LOG_ERR, "[client_for_ticket] Failed to get location query result");
    }

    $client_data = mysqli_fetch_assoc($client_result);
    if (!isset($client_data)) {
        log_app(LOG_ERR, "[client_for_ticket] Failed to get location data");
    }

    return strtolower($client_data["client"]);
}

function status_for_ticket(int $ticket_id)
{
	global $database;

    $status_result = $database->execute_query("SELECT status FROM help.tickets WHERE tickets.id = ?", [$ticket_id]);
    if (!isset($status_result)) {
        log_app(LOG_ERR, "[status_for_ticket] Failed to get status query result");
    }

    $status_data = mysqli_fetch_assoc($status_result);
    if (!isset($status_data)) {
        log_app(LOG_ERR, "[status_for_ticket] Failed to get status data");
    }

    return $status_data["status"];
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
	global $database;

	$priorityTypes = [1 => "Critical", 3 => "Urgent", 5 => "High", 10 => "Standard", 15 => "Client Response", 30 => "Project", 60 => "Meeting Support"];

	$tickets = [];
	while ($row = $ticket_data->fetch_assoc()) {
		$tmp = [];
		$tmp["id"] = $row["id"];

		$row_color = '';
        if (isset($row["alert_levels"])) {
            $alert_levels = explode(',', $row["alert_levels"]);
            foreach ($alert_levels as $alert_level) {
                $row_color .= trim($alert_level) . ' ';
            }
        }

		$tmp["row_color"] =  $row_color;
		$tmp["title"] = $row["name"];
		$tmp["description"] = limitChars(strip_tags(html_entity_decode($row["description"])), 100);

		$notes_query = "SELECT creator, note FROM help.notes WHERE linked_id = ? ORDER BY
			(CASE WHEN date_override IS NULL THEN created ELSE date_override END) DESC
		";
		$notes_stmt = mysqli_prepare($database, $notes_query);
		$creator = null;
		$note_data = null;
		if ($notes_stmt) {
			mysqli_stmt_bind_param($notes_stmt, "i", $row["id"]);
			mysqli_stmt_execute($notes_stmt);

			mysqli_stmt_bind_result($notes_stmt, $creator, $note_data);
			// Fetch the result
			mysqli_stmt_fetch($notes_stmt);

			// Use $location_name as needed
			mysqli_stmt_close($notes_stmt);
		}
		$latest_note_str = "";
		if ($creator != null && $note_data != null) {
			$tmp["latest_note_author"] = $creator;
			$tmp["latest_note"] = limitChars(strip_tags(html_entity_decode($note_data)), 150);
		}

		$tmp["client_username"] = $row["client"];
		if (isset($row["client"])) {
            $result = get_client_name($row["client"]);
        }

		$tmp["client_first_name"] = $result['firstname'] ?: "";
		$tmp["client_last_name"] = $result['lastname'] ?: "";

        $location_query = "SELECT location_name FROM locations WHERE sitenumber = ?";
        $loc_stmt = mysqli_prepare($database, $location_query);
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
            $request_type_query_result = $database->execute_query("SELECT request_name FROM request_type WHERE request_id = ?", [$row['request_type_id']]);
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
		$tmp["assigned_tech"] = $row["employee"];
		$tmp["alert_level"] = isset($row["alert_levels"]) ? $row["alert_levels"] : '';
		$tickets[] = $tmp;
	}

	return $tickets;
}