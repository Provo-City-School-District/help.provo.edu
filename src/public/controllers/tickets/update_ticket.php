<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');

require("ticket_utils.php");
require_once("email_utils.php");
require_once("template.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_client = null;
    $old_assigned_email = null;

    // Retrieve updated values from the form
    $ticket_id = trim(htmlspecialchars($_POST['ticket_id']));
    $updatedClient = trim(htmlspecialchars($_POST['client']));
    $updatedEmployee = trim(htmlspecialchars($_POST['employee']));
    $updatedLocation = trim(htmlspecialchars($_POST['location']));
    $updatedRoom = trim(htmlspecialchars($_POST['room']));
    $updatedName = trim(htmlspecialchars($_POST['ticket_name']));
    $updatedDescription = trim(htmlspecialchars($_POST['description']));
    $updatedDueDate = trim(htmlspecialchars($_POST['due_date']));
    $updatedStatus = trim(htmlspecialchars($_POST['status']));
    $updatedby = trim(htmlspecialchars($_POST['madeby']));
    $updatedPhone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);

    $updatedCCEmails = filter_input(INPUT_POST, 'cc_emails', FILTER_SANITIZE_SPECIAL_CHARS);

    $updatedSendClientEmail = isset($_POST['send_client_email']) ? 1 : 0;
    $updatedSendTechEmail = isset($_POST['send_tech_email']) ? 1 : 0;
    $updatedSendCCEmails = isset($_POST['send_cc_emails']) ? 1 : 0;
    $updatedSendBCCEmails = isset($_POST['send_bcc_emails']) ? 1 : 0;
    $updatedInternTicketStatus = isset($_POST['intern_ticket_status']) ? 1 : 0;


    if ($updatedStatus == "resolved") {
        $notes_result = HelpDB::get()->execute_query("SELECT COUNT(*) as count FROM notes WHERE linked_id = ?", [$ticket_id]);
        $row = mysqli_fetch_assoc($notes_result);
        if ($row['count'] == 0) {
            // Stop the resolving process and display an error message
            $noNote_error = "Cannot resolve ticket without adding a note";
            $_SESSION['current_status'] = $noNote_error;
            $_SESSION['status_type'] = 'error';
            header("Location: edit_ticket.php?$formData&id=$ticket_id");
            exit;
        }
    }

    $assigned_tech_changed = false;
    $master_send_emails = true;
    if (isset($_POST["update_ticket"])) {
        $master_send_emails = false;
    }

    // Prevent ticket from being closed in any way if tasks are not complete
    if ($updatedStatus == "resolved" || $updatedStatus == "closed") {
        $tasks_result = HelpDB::get()->execute_query("SELECT COUNT(*) as count FROM ticket_tasks WHERE (ticket_id = ? AND required = 1 AND completed != 1)", [$ticket_id]);
        $row = mysqli_fetch_assoc($tasks_result);
        if ($row['count'] != 0) {
            // Stop the resolving process and display an error message
            $error = "Cannot resolve with required, uncompleted tasks";
            $_SESSION['current_status'] = $error;
            $_SESSION['status_type'] = 'error';
            header("Location: edit_ticket.php?$formData&id=$ticket_id");
            exit;
        }
    }


    // Allow trailing comma
    if (substr($updatedCCEmails, -1) == ",") {
        $updatedCCEmails = substr_replace($updatedCCEmails, '', -1, 1);
    }

    // Allow trailing comma
    $updatedBCCEmails = filter_input(INPUT_POST, 'bcc_emails', FILTER_SANITIZE_SPECIAL_CHARS);
    if (substr($updatedBCCEmails, -1) == ",") {
        $updatedBCCEmails = substr_replace($updatedBCCEmails, '', -1, 1);
    }

    $updatedRequestType = trim(htmlspecialchars($_POST['request_type']));
    $updatedPriority = trim(htmlspecialchars($_POST['priority']));
    $updatedParentTicket = intval(trim(htmlspecialchars($_POST['parent_ticket'])));
    $changesMessage = "";
    $created_at = trim(htmlspecialchars($_POST['ticket_create_date']));

    // Validate the emails in CC and BCC fields
    $valid_cc_emails = [];
    if (trim($updatedCCEmails) !== "") {
        $valid_cc_emails = split_email_string_to_arr($updatedCCEmails);
        if (!$valid_cc_emails) {
            $error = 'Error parsing CC emails (invalid format)';
            $formData = http_build_query($_POST);
            $_SESSION['current_status'] = $error;
            $_SESSION['status_type'] = 'error';
            header("Location: edit_ticket.php?$formData&id=$ticket_id");
            exit;
        }
    }

    $valid_bcc_emails = [];
    if (trim($updatedBCCEmails) !== "") {
        $valid_bcc_emails = split_email_string_to_arr($updatedBCCEmails);
        if (!$valid_bcc_emails) {
            $error = 'Error parsing BCC emails (invalid format)';
            $formData = http_build_query($_POST);
            $_SESSION['current_status'] = $error;
            $_SESSION['status_type'] = 'error';
            header("Location: edit_ticket.php?$formData&id=$ticket_id");
            exit;
        }
    }


    // Get the old ticket data
    $old_ticket_query = "SELECT * FROM tickets WHERE id = ?";
    $old_ticket_stmt = mysqli_prepare(HelpDB::get(), $old_ticket_query);
    mysqli_stmt_bind_param($old_ticket_stmt, "i", $ticket_id);
    mysqli_stmt_execute($old_ticket_stmt);
    $old_ticket_result = mysqli_stmt_get_result($old_ticket_stmt);
    $old_ticket_data = mysqli_fetch_assoc($old_ticket_result);


    // Recalculate the due date if the priority has changed
    if (isset($old_ticket_data['priority'], $updatedPriority) && $old_ticket_data['priority'] != $updatedPriority) {
        $today = new DateTime($created_at);
        // echo "Created Date is: " . $today->format('Y-m-d') . "<br>";
        $new_due_date = clone $today;

        // Add the priority days to the due date, skipping weekends
        $days_to_add = intval($updatedPriority);
        // echo "Days to add: " . $days_to_add . "<br>";
        while ($days_to_add > 0) {
            $new_due_date->modify('+1 day');
            // If the day is a weekday, decrement $days_to_add
            if ($new_due_date->format('N') < 6 && !isExcludedDate($new_due_date->format('Y-m-d'))) {
                $days_to_add--;
            }
        }
        // echo "New Due Date is: " . $new_due_date->format('Y-m-d') . "<br>";
        $updatedDueDate = $new_due_date->format('Y-m-d');
    }



    // Perform SQL UPDATE queries to update the ticket information
    $update_ticket_query = "UPDATE tickets SET
        client = ?,
        employee = ?,
        location = ?,
        room = ?,
        name = ?,
        description = ?,
        due_date = ?,
        status = ?,
        phone = ?,
        cc_emails = ?,
        bcc_emails = ?,
        priority = ?,
        request_type_id = ?,
        parent_ticket = ?,
        last_updated = NOW(),
        send_client_email = ?,
        send_tech_email = ?,
        send_cc_emails = ?,
        send_bcc_emails = ?,
        intern_visible = ?
        WHERE id = ?";

    $update_ticket_query_vars = [
        $updatedClient, $updatedEmployee, $updatedLocation, $updatedRoom, $updatedName,
        $updatedDescription, $updatedDueDate, $updatedStatus, $updatedPhone, $updatedCCEmails,
        $updatedBCCEmails, $updatedPriority, $updatedRequestType, $updatedParentTicket,
        $updatedSendClientEmail, $updatedSendTechEmail, $updatedSendCCEmails, $updatedSendBCCEmails, 
        $updatedInternTicketStatus, $ticket_id
    ];

    // Execute the update queries
    $updateTicketResult = HelpDB::get()->execute_query($update_ticket_query, $update_ticket_query_vars);

    if (!$updateTicketResult) {
        die('Error updating ticket: ' . mysqli_error(HelpDB::get()));
    }

    // Columns for the ticket_logs table
    $clientColumn  = "client";
    $employeeColumn  = "employee";
    $locationColumn  = "location";
    $roomColumn  = "room";
    $nameColumn  = "name";
    $descriptionColumn  = "description";
    $dueDateColumn  = "due_date";
    $statusColumn  = "status";
    $phoneColumn  = "phone";
    $priorityColumn  = "priority";
    $requestTypeColumn  = "request_type_id";
    $ccEmailsColumn = "cc_emails";
    $bccEmailsColumn = "bcc_emails";
    $parentTicketColumn = "parent_ticket";

    // Log the ticket changes and build message of changes for email
    if (isset($old_ticket_data['priority'], $updatedPriority) && $old_ticket_data['priority'] != $updatedPriority) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $priorityColumn, $old_ticket_data['priority'], $updatedPriority);
        $changesMessage .= "<li>Changed Priority from " . $priorityTypes[$old_ticket_data['priority']] . " to " . $priorityTypes[$updatedPriority] . "</li>";
    }

    if (isset($old_ticket_data['request_type_id'], $updatedRequestType) && $old_ticket_data['request_type_id'] != $updatedRequestType) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $requestTypeColumn, $old_ticket_data['request_type_id'], $updatedRequestType);
        $changesMessage .= "<li>Changed Request Type from '" . request_name_for_type($old_ticket_data['request_type_id']) . "' to '" . request_name_for_type($updatedRequestType) . "'</li>";
    }

    if (isset($old_ticket_data['client'], $updatedClient) && $old_ticket_data['client'] != $updatedClient) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $clientColumn, $old_ticket_data['client'], $updatedClient);
        $changesMessage .= "<li>Changed Client from " . $old_ticket_data['client'] . " to " . $updatedClient . "</li>";
        $old_client = email_address_from_username($old_ticket_data['client']);
    }

    if (isset($old_ticket_data['employee'], $updatedEmployee) && $old_ticket_data['employee'] != $updatedEmployee) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $employeeColumn, $old_ticket_data['employee'], $updatedEmployee);

        $assigned_tech_changed = true;

        // Handle the case where the employee is unassigned
        if ($old_ticket_data['employee'] !== null && $old_ticket_data['employee'] !== 'unassigned') {

            // this will force emails
            $old_assigned_email = email_address_from_username($old_ticket_data['employee']);
        } else {
            $old_ticket_data['employee'] = "unassigned";
        }
        $changesMessage .= "<li>Changed Employee from " . $old_ticket_data['employee'] . " to " . $updatedEmployee . "</li>";
        // If the ticket was re-assigned, remove the alerts, they will be re-added within an hour for the new user.
        removeAllAlertsByTicketId($ticket_id);
    } else if (!isset($old_ticket_data['employee'])) {
        $assigned_tech_changed = true;
    }

    if (isset($old_ticket_data['location'], $updatedLocation) && $old_ticket_data['location'] != $updatedLocation) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $locationColumn, $old_ticket_data['location'], $updatedLocation);
        $changesMessage .= "<li>Changed Location from " . $old_ticket_data['location'] . " to " . $updatedLocation . "</li>";
    }

    if (isset($old_ticket_data['room'], $updatedRoom) && $old_ticket_data['room'] != $updatedRoom) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $roomColumn, $old_ticket_data['room'], $updatedRoom);
        $changesMessage .= "<li>Changed Room from " . $old_ticket_data['room'] . " to " . $updatedRoom . "</li>";
    }

    if (isset($old_ticket_data['name'], $updatedName) && $old_ticket_data['name'] != $updatedName) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $nameColumn, $old_ticket_data['name'], $updatedName);
        $changesMessage .= "<li>Changed Subject from " . $old_ticket_data['name'] . " to " . $updatedName . "</li>";
    }

    if (isset($old_ticket_data['description'], $updatedDescription) && html_entity_decode($old_ticket_data['description']) != html_entity_decode($updatedDescription)) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $descriptionColumn, $old_ticket_data['description'], $updatedDescription);
        $changesMessage .= "<li>Changed Description from " . html_entity_decode($old_ticket_data['description']) . " to " . html_entity_decode($updatedDescription) . "</li>";
    }

    if (isset($old_ticket_data['due_date'], $updatedDueDate) && $old_ticket_data['due_date'] != $updatedDueDate) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $dueDateColumn, $old_ticket_data['due_date'], $updatedDueDate);
        removeAlert(HelpDB::get(), $pastDueMessage, $ticket_id);
        $changesMessage .= "<li>Changed Due Date from " . $old_ticket_data['due_date'] . " to " . $updatedDueDate . "</li>";
    }

    if (isset($old_ticket_data['status'], $updatedStatus) && $old_ticket_data['status'] != $updatedStatus) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $statusColumn, $old_ticket_data['status'], $updatedStatus);
        $changesMessage .= "<li>Changed Status from " . $old_ticket_data['status'] . " to " . $updatedStatus . "</li>";
        if ($updatedStatus == "resolved" || $updatedStatus == "closed") {
            // Status was changed, purge all alerts, they will get pushed back in within an hour for the new status
            removeAllAlertsByTicketId($ticket_id);
        }
    }

    if (isset($old_ticket_data['phone'], $updatedPhone) && $old_ticket_data['phone'] != $updatedPhone) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $phoneColumn, $old_ticket_data['phone'], $updatedPhone);
        $changesMessage .= "<li>Changed Phone from " . $old_ticket_data['phone'] . " to " . $updatedPhone . "</li>";
    }

    if (isset($old_ticket_data['cc_emails'], $updatedCCEmails) && $old_ticket_data['cc_emails'] != $updatedCCEmails) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $ccEmailsColumn, $old_ticket_data['cc_emails'], $updatedCCEmails);
        if ($old_ticket_data['cc_emails'] == "") {
            $changesMessage .= "<li>Added CC Emails " . $updatedCCEmails . "</li>";
        } else {
            $changesMessage .= "<li>Changed CC Emails from " . $old_ticket_data['cc_emails'] . " to " . $updatedCCEmails . "</li>";
        }
    }

    if (isset($old_ticket_data['bcc_emails'], $updatedBCCEmails) && $old_ticket_data['bcc_emails'] != $updatedBCCEmails) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $bccEmailsColumn, $old_ticket_data['bcc_emails'], $updatedBCCEmails);
        if ($old_ticket_data['bcc_emails']) {
            $changesMessage .= "<li>Added BCC Emails " . $updatedBCCEmails . "</li>";
        } else {
            $changesMessage .= "<li>Changed BCC Emails from " . $old_ticket_data['bcc_emails'] . " to " . $updatedBCCEmails . "</li>";
        }
    }

    if (isset($old_ticket_data['parent_ticket'], $updatedParentTicket) && $old_ticket_data['parent_ticket'] != $updatedParentTicket) {
        logTicketChange(HelpDB::get(), $ticket_id, $updatedby, $parentTicketColumn, $old_ticket_data['parent_ticket'], $updatedParentTicket);
        $changesMessage .= "<li>Changed Parent Ticket from " . $old_ticket_data['parent_ticket'] . " to " . $updatedParentTicket . "</li>";
    }


    // Check if the ticket has an alert about not being updated in last 48 hours and clear it since the ticket was just updated.
    removeAlert(HelpDB::get(), $alert48Message, $ticket_id);
    removeAlert(HelpDB::get(), $alert7DayMessage, $ticket_id);
    removeAlert(HelpDB::get(), $alert15DayMessage, $ticket_id);
    removeAlert(HelpDB::get(), $alert20DayMessage, $ticket_id);


    // After successfully updating the ticket, set a success message;
    $msg = "Ticket updated successfully.";

    // Get the last 3 notes for the ticket
    $notes = get_ticket_notes($ticket_id, 3);
    $notesMessageClient = "";
    $notesMessageTech = "";

    if (count($notes) > 0) {
        $notesMessageClient .= "<tr><th>Date</th><th>Creator</th><th>Note</th></tr>";
        $notesMessageTech .= "<tr><th>Date</th><th>Creator</th><th>Note</th></tr>";
    }

    foreach ($notes as $note) {
        $dateOverride = $note['date_override'];
        $effectiveDate = $dateOverride;
        if ($dateOverride == null)
            $effectiveDate = $note['created'];

        $dateStr = date_format(date_create($effectiveDate), "F jS\, Y h:i:s A");
        $noteCreator = $note['creator'];
        $decodedNote = htmlspecialchars_decode($note['note']);

        $note_theme = "";
        if (!user_is_tech($noteCreator)) {
            $note_theme = "nonTech";
        } else if ($note['visible_to_client'] == 0) {
            $note_theme = "notClientVisible";
        } else {
            $note_theme = "clientVisible";
        }

        $notesMessageTech .= "<tr><td>$dateStr</td><td>$noteCreator</td><td><span class=\"$note_theme\">$decodedNote</span></td></tr>";
        if ($note['visible_to_client']) {
            $notesMessageClient .= "<tr><td>$dateStr</td><td>$noteCreator</td><td><span class=\"$note_theme\">$decodedNote</span></td></tr>";
        }
    }

    $tech_cc_emails = [];
    $client_cc_emails = [];
    $tech_bcc_emails = [];
    $client_bcc_emails = [];

    // Only add all cc/bcc emails if we're sending them
    if ($updatedSendCCEmails) {
        foreach ($valid_cc_emails as $email) {
            $email_username = username_from_email_address($email);
            if (user_is_tech($email_username))
                $tech_cc_emails[] = $email;
            else
                $client_cc_emails[] = $email;
        }
    }

    if ($updatedSendBCCEmails) {
        foreach ($valid_bcc_emails as $email) {
            $email_username = username_from_email_address($email);
            if (user_is_tech($email_username))
                $tech_bcc_emails[] = $email;
            else
                $client_bcc_emails[] = $email;
        }
    }

    if ($updatedStatus == "resolved") {
        $template_path = "ticket_resolved";
        $subject_status = "Resolved";
    } else {
        $template_path = "ticket_updated";
        $subject_status = "Updated";
    }

    // message for gui to display
    $client_email = email_address_from_username($updatedClient);
    $ticket_subject = "Ticket " . $ticket_id . " ($subject_status) - " . $updatedName;
    $client_name = get_client_name($updatedClient);
    $location_name = location_name_from_id($updatedLocation);
    $assigned_tech_email = email_address_from_username($updatedEmployee);

    $template_tech = new Template(from_root("/includes/templates/{$template_path}_tech.phtml"));

    $template_tech->client = $client_name["firstname"] . " " . $client_name["lastname"];
    $template_tech->location = $location_name;
    $template_tech->ticket_id = $ticket_id;
    $template_tech->changes_message = $changesMessage;
    $template_tech->notes_message = $notesMessageTech;
    $template_tech->site_url = getenv('ROOTDOMAIN');
    $template_tech->description = html_entity_decode($updatedDescription);
    $template_tech->room = empty($updatedRoom) ? "<empty>" : $updatedRoom;
    $template_tech->phone = empty($updatedPhone) ? "<empty>" : $updatedPhone;

    $remaining_tasks_query = "SELECT assigned_tech, description FROM ticket_tasks WHERE (completed != 1 AND ticket_id = ?)";

    $remaining_tasks_result = HelpDB::get()->execute_query($remaining_tasks_query, [$ticket_id]);
    $remaining_tasks = "";

    while ($row = $remaining_tasks_result->fetch_assoc()) {
        $tech_name = get_local_name_for_user($row["assigned_tech"]);
        $tech = "Unassigned";
        if ($tech_name != null) {
            $tech = $tech_name["firstname"]." ".$tech_name["lastname"];
        }
        $desc = $row["description"];
        $remaining_tasks[] =  ["tech_name" => $tech_name, "description" => $desc];
    }


    $template_tech->remaining_tasks = $remaining_tasks;


    $template_client = new Template(from_root("/includes/templates/{$template_path}_client.phtml"));

    $template_client->client = $client_name["firstname"] . " " . $client_name["lastname"];
    $template_client->location = $location_name;
    $template_client->ticket_id = $ticket_id;
    $template_client->notes_message = $notesMessageClient;
    $template_client->site_url = getenv('ROOTDOMAIN');
    $template_client->description = html_entity_decode($updatedDescription);

    $select_attachments_query = "SELECT attachment_path from help.tickets WHERE id = ?";
    $stmt = mysqli_prepare(HelpDB::get(), $select_attachments_query);
    mysqli_stmt_bind_param($stmt, "i", $ticket_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$result) {
        log_app(LOG_ERR, "Failed to get old attachment_path");
    }
    mysqli_stmt_close($stmt);

    $attachment_paths = explode(',', $result["attachment_path"]);

    $sent_tech_email_log_msg = "Sent tech-privileged emails to: ";
    $sent_client_email_log_msg = "Sent non-tech emails to: ";

    // Force reassigned ticket email regardless of master_send_emails status
    // Send to new tech and old tech
    $send_errors = [];

    if ($assigned_tech_changed) {
        if ($updatedEmployee != "unassigned") {
            $assigned_tech_name = get_local_name_for_user($updatedEmployee);
            $firstname = ucfirst(strtolower($assigned_tech_name["firstname"]));
            $lastname = ucfirst(strtolower($assigned_tech_name["lastname"]));

            $new_subject = "Ticket $ticket_id has been reassigned to $firstname $lastname";
        } else {
            $new_subject = "Ticket $ticket_id has been unassigned";
        }

        log_app(LOG_INFO, "[update_ticket.php] Sent assignment emails");

        $res = send_email_and_add_to_ticket($ticket_id, $assigned_tech_email, $new_subject, $template_tech, [], [], $attachment_paths);
        if (!$res) {
            $send_errors[] = "Newly Assigned Tech";
        } else {
            $sent_tech_email_log_msg .= $assigned_tech_email . ", ";
        }

        if (isset($old_assigned_email)) {
            $res = send_email_and_add_to_ticket($ticket_id, $old_assigned_email, $new_subject, $template_tech, [], [], $attachment_paths);
            if (!$res) {
                $send_errors[] = "Previously Assigned Tech";
            } else {
                $sent_tech_email_log_msg .= $old_assigned_email . ", ";
            }
        }
    } else {
        log_app(LOG_INFO, "[update_ticket.php] Did not send assignment emails");
    }

    if ($master_send_emails) {
        $noDuplicateTechEmail = ($updatedClient != $updatedEmployee) || !$updatedSendTechEmail;
        if ($updatedSendClientEmail && $noDuplicateTechEmail) {
            $res = send_email_and_add_to_ticket($ticket_id, $client_email, $ticket_subject, $template_client, [], [], $attachment_paths);
            if (!$res) {
                $send_errors[] = "Client";
            } else {
                $sent_client_email_log_msg .= $client_email . ", ";
            }
        }

        // if assigned_tech_changed is true, tech was already sent an email
        if ($updatedSendTechEmail && !$assigned_tech_changed) {
            $res = send_email_and_add_to_ticket($ticket_id, $assigned_tech_email, $ticket_subject, $template_tech, [], [], $attachment_paths);
            if (!$res) {
                $send_errors[] = "Tech";
            } else {
                $sent_tech_email_log_msg .= $assigned_tech_email . ", ";
            }
        }

        if ($updatedSendCCEmails) {
            $res = send_email_and_add_to_ticket($ticket_id, getenv("GMAIL_USER"), $ticket_subject, $template_tech, $tech_cc_emails, [], $attachment_paths);
            if (!$res) {
                $send_errors[] = "Tech CC Emails";
            } else {
                $sent_tech_email_log_msg .= implode(',', $tech_cc_emails) . ", ";
            }

            $res = send_email_and_add_to_ticket($ticket_id, getenv("GMAIL_USER"), $ticket_subject, $template_client, $client_cc_emails, [], $attachment_paths);
            if (!$res) {
                $send_errors[] = "Client CC Emails";
            } else {
                $sent_client_email_log_msg .= implode(',', $client_cc_emails) . ", ";
            }
        }

        if ($updatedSendBCCEmails) {
            $res = send_email_and_add_to_ticket($ticket_id, getenv("GMAIL_USER"), $ticket_subject, $template_tech, [], $tech_bcc_emails, $attachment_paths);
            if (!$res) {
                $send_errors[] = "Tech BCC Emails";
            } else {
                $sent_tech_email_log_msg .= implode(',', $tech_bcc_emails) . ", ";
            }
            $res = send_email_and_add_to_ticket($ticket_id, getenv("GMAIL_USER"), $ticket_subject, $template_client, [], $client_bcc_emails, $attachment_paths);
            if (!$res) {
                $send_errors[] = "Client BCC Emails";
            } else {
                $sent_client_email_log_msg .= implode(',', $client_bcc_emails) . ", ";
            }
        }
    }


    if (count($send_errors) > 0) {
        log_app(LOG_INFO, "Email errors found");
        $error_str = "Error sending email(s) to: ";
        foreach ($send_errors as $error) {
            $error_str .= $error . ", ";
        }

        $_SESSION['current_status'] = $error_str;
        $_SESSION['status_type'] = 'error';

        $formData = http_build_query($_POST);
        log_app(LOG_ERR, "$error \n\n $formData");
        header("Location: edit_ticket.php?$formData&id=$ticket_id");
        exit;
    } else if ($master_send_emails) {
        /* 
            Only modify the client's message if they explicitly updated ticket with emails sent
            (even though some emails always get sent)
        */
        $send_to_msg_states = [
            "",
            "An email was sent to the BCC emails",
            "An email was sent to the CC emails",
            "An email was sent to the CC emails and BCC emails",
            "An email was sent to the tech",
            "An email was sent to the tech and BCC emails",
            "An email was sent to the tech and CC emails",
            "An email was sent to the tech, CC emails, and BCC emails",
            "An email was sent to the client",
            "An email was sent to the client and BCC emails",
            "An email was sent to the client and CC emails",
            "An email was sent to the client, CC emails, and BCC emails",
            "An email was sent to the client and the tech",
            "An email was sent to the client, tech, and BCC emails",
            "An email was sent to the client, tech, and CC emails",
            "An email was sent to the client, tech, CC emails, and BCC emails",
        ];
        $state_idx = ($updatedSendClientEmail << 3) | ($updatedSendTechEmail << 2) | ($updatedSendCCEmails << 1) | $updatedSendBCCEmails;
        assert($state_idx < 16, "Expected state_idx to be < 16");
        $msg .= " " . $send_to_msg_states[$state_idx];
    }

    logTicketChange(HelpDB::get(), $ticket_id, $_SESSION["username"], "sent_emails", "N/A", $sent_tech_email_log_msg . " " . $sent_client_email_log_msg);


    $_SESSION['current_status'] = $msg;
    $_SESSION['status_type'] = "success";

    // Redirect to the same page after successful update
    header('Location: edit_ticket.php?id=' . $ticket_id);
    exit;
}
