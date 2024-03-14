<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');

require("ticket_utils.php");
require("email_utils.php");
require("template.php");

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


    if ($updatedStatus == "resolved") {
        $notes_result = $database->execute_query("SELECT COUNT(*) as count FROM notes WHERE linked_id = ?", [$ticket_id]);
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
    $old_ticket_stmt = mysqli_prepare($database, $old_ticket_query);
    mysqli_stmt_bind_param($old_ticket_stmt, "i", $ticket_id);
    mysqli_stmt_execute($old_ticket_stmt);
    $old_ticket_result = mysqli_stmt_get_result($old_ticket_stmt);
    $old_ticket_data = mysqli_fetch_assoc($old_ticket_result);


    // Recalculate the due date if the priority has changed
    if (isset($old_ticket_data['priority'], $updatedPriority) && $old_ticket_data['priority'] != $updatedPriority) {
        $today = new DateTime($created_at);
        $new_due_date = clone $today;
        $new_due_date->modify("+{$updatedPriority} weekdays");
        $has_excludes = hasExcludedDate($today->format('Y-m-d'), $new_due_date->format('Y-m-d'));
        $new_due_date->modify("+{$has_excludes} days");
        $updatedDueDate = $new_due_date->format('y-m-d');
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
        last_updated = NOW()
        WHERE id = ?";

    $update_ticket_query_vars = [$updatedClient, $updatedEmployee, $updatedLocation, $updatedRoom, $updatedName,
        $updatedDescription, $updatedDueDate, $updatedStatus, $updatedPhone, $updatedCCEmails,
        $updatedBCCEmails, $updatedPriority, $updatedRequestType, $updatedParentTicket, $ticket_id];

    // Execute the update queries
    $updateTicketResult = $database->execute_query($update_ticket_query, $update_ticket_query_vars);

    if (!$updateTicketResult) {
        die('Error updating ticket: ' . mysqli_error($database));
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
        logTicketChange($database, $ticket_id, $updatedby, $priorityColumn, $old_ticket_data['priority'], $updatedPriority);
        $changesMessage .= "<li>Changed Priority from " . $priorityTypes[$old_ticket_data['priority']] . " to " . $priorityTypes[$updatedPriority] . "</li>";
    }

    if (isset($old_ticket_data['request_type_id'], $updatedRequestType) && $old_ticket_data['request_type_id'] != $updatedRequestType) {
        logTicketChange($database, $ticket_id, $updatedby, $requestTypeColumn, $old_ticket_data['request_type_id'], $updatedRequestType);
        $changesMessage .= "<li>Changed Request Type from " . $old_ticket_data['request_type_id'] . " to " . $updatedRequestType . "</li>";
    }

    if (isset($old_ticket_data['client'], $updatedClient) && $old_ticket_data['client'] != $updatedClient) {
        logTicketChange($database, $ticket_id, $updatedby, $clientColumn, $old_ticket_data['client'], $updatedClient);
        $changesMessage .= "<li>Changed Client from " . $old_ticket_data['client'] . " to " . $updatedClient . "</li>";
        $old_client = email_address_from_username($old_ticket_data['client']);
    }

    if (isset($old_ticket_data['employee'], $updatedEmployee) && $old_ticket_data['employee'] != $updatedEmployee) {
        logTicketChange($database, $ticket_id, $updatedby, $employeeColumn, $old_ticket_data['employee'], $updatedEmployee);
        // Handle the case where the employee is unassigned
        if ($old_ticket_data['employee'] !== null && $old_ticket_data['employee'] !== 'unassigned') {
            $old_assigned_email = email_address_from_username($old_ticket_data['employee']);
        } else {
            $old_ticket_data['employee'] = "unassigned";
        }
        $changesMessage .= "<li>Changed Employee from " . $old_ticket_data['employee'] . " to " . $updatedEmployee . "</li>";
        // If the ticket was re-assigned, remove the alert
        removeAlert($database, $pastDueMessage, $ticket_id);
        removeAlert($database, $alert48Message, $ticket_id);
        removeAlert($database, $alert7DayMessage, $ticket_id);
        removeAlert($database, $alert15DayMessage, $ticket_id);
        removeAlert($database, $alert20DayMessage, $ticket_id);
    }

    if (isset($old_ticket_data['location'], $updatedLocation) && $old_ticket_data['location'] != $updatedLocation) {
        logTicketChange($database, $ticket_id, $updatedby, $locationColumn, $old_ticket_data['location'], $updatedLocation);
        $changesMessage .= "<li>Changed Location from " . $old_ticket_data['location'] . " to " . $updatedLocation . "</li>";
    }

    if (isset($old_ticket_data['room'], $updatedRoom) && $old_ticket_data['room'] != $updatedRoom) {
        logTicketChange($database, $ticket_id, $updatedby, $roomColumn, $old_ticket_data['room'], $updatedRoom);
        $changesMessage .= "<li>Changed Room from " . $old_ticket_data['room'] . " to " . $updatedRoom . "</li>";
    }

    if (isset($old_ticket_data['name'], $updatedName) && $old_ticket_data['name'] != $updatedName) {
        logTicketChange($database, $ticket_id, $updatedby, $nameColumn, $old_ticket_data['name'], $updatedName);
        $changesMessage .= "<li>Changed Subject from " . $old_ticket_data['name'] . " to " . $updatedName . "</li>";
    }

    if (isset($old_ticket_data['description'], $updatedDescription) && html_entity_decode($old_ticket_data['description']) != html_entity_decode($updatedDescription)) {
        logTicketChange($database, $ticket_id, $updatedby, $descriptionColumn, $old_ticket_data['description'], $updatedDescription);
        $changesMessage .= "<li>Changed Description from " . html_entity_decode($old_ticket_data['description']) . " to " . html_entity_decode($updatedDescription) . "</li>";
    }

    if (isset($old_ticket_data['due_date'], $updatedDueDate) && $old_ticket_data['due_date'] != $updatedDueDate) {
        logTicketChange($database, $ticket_id, $updatedby, $dueDateColumn, $old_ticket_data['due_date'], $updatedDueDate);
        removeAlert($database, $pastDueMessage, $ticket_id);
        $changesMessage .= "<li>Changed Due Date from " . $old_ticket_data['due_date'] . " to " . $updatedDueDate . "</li>";
    }

    if (isset($old_ticket_data['status'], $updatedStatus) && $old_ticket_data['status'] != $updatedStatus) {
        logTicketChange($database, $ticket_id, $updatedby, $statusColumn, $old_ticket_data['status'], $updatedStatus);
        $changesMessage .= "<li>Changed Status from " . $old_ticket_data['status'] . " to " . $updatedStatus . "</li>";
        if ($updatedStatus == "resolved") {
            removeAlert($database, $pastDueMessage, $ticket_id);
        }
    }

    if (isset($old_ticket_data['phone'], $updatedPhone) && $old_ticket_data['phone'] != $updatedPhone) {
        logTicketChange($database, $ticket_id, $updatedby, $phoneColumn, $old_ticket_data['phone'], $updatedPhone);
        $changesMessage .= "<li>Changed Phone from " . $old_ticket_data['phone'] . " to " . $updatedPhone . "</li>";
    }

    if (isset($old_ticket_data['cc_emails'], $updatedCCEmails) && $old_ticket_data['cc_emails'] != $updatedCCEmails) {
        logTicketChange($database, $ticket_id, $updatedby, $ccEmailsColumn, $old_ticket_data['cc_emails'], $updatedCCEmails);
        if ($old_ticket_data['cc_emails'] == "") {
            $changesMessage .= "<li>Added CC Emails " . $updatedCCEmails . "</li>";
        } else {
            $changesMessage .= "<li>Changed CC Emails from " . $old_ticket_data['cc_emails'] . " to " . $updatedCCEmails . "</li>";
        }
    }

    if (isset($old_ticket_data['bcc_emails'], $updatedBCCEmails) && $old_ticket_data['bcc_emails'] != $updatedBCCEmails) {
        logTicketChange($database, $ticket_id, $updatedby, $bccEmailsColumn, $old_ticket_data['bcc_emails'], $updatedBCCEmails);
        if ($old_ticket_data['bcc_emails']) {
            $changesMessage .= "<li>Added BCC Emails " . $updatedBCCEmails . "</li>";
        } else {
            $changesMessage .= "<li>Changed BCC Emails from " . $old_ticket_data['bcc_emails'] . " to " . $updatedBCCEmails . "</li>";
        }
    }

    if (isset($old_ticket_data['parent_ticket'], $updatedParentTicket) && $old_ticket_data['parent_ticket'] != $updatedParentTicket) {
        logTicketChange($database, $ticket_id, $updatedby, $parentTicketColumn, $old_ticket_data['parent_ticket'], $updatedParentTicket);
        $changesMessage .= "<li>Changed Parent Ticket from " . $old_ticket_data['parent_ticket'] . " to " . $updatedParentTicket . "</li>";
    }


    // Check if the ticket has an alert about not being updated in last 48 hours and clear it since the ticket was just updated.
    removeAlert($database, $alert48Message, $ticket_id);
    removeAlert($database, $alert7DayMessage, $ticket_id);

    $send_client_email = isset($_POST['send_emails']) && ($_POST['send_emails'] == "send_emails");
    $send_cc_bcc_emails = isset($_POST['send_cc_bcc_emails']) && ($_POST['send_cc_bcc_emails'] == "send_cc_bcc_emails");

    // Force emails if status was resolved or pending
    if ($updatedStatus == "pending" || $updatedStatus == "resolved") {
        $send_client_email = true;
        $send_cc_bcc_emails = true;
    }

    // After successfully updating the ticket, set a success message;
    $msg = "Ticket updated successfully.";

    // Get the last 3 notes for the ticket
    $notes = get_ticket_notes($ticket_id, 3);
    $notesMessageClient = "";
    $notesMessageTech = "";

    foreach ($notes as $note) {
        $dateOverride = $note['date_override'];
        $effectiveDate = $dateOverride;
        if ($dateOverride == null)
            $effectiveDate = $note['created'];


        $dateStr = date_format(date_create($effectiveDate), "F jS\, Y\: h:i:s A");
        $noteCreator = $note['creator'];
        $decodedNote = $noteCreator . " ($dateStr): " . htmlspecialchars_decode($note['note']);
        $notesMessageTech .= "<li>" . $decodedNote . "</li>";
        if ($note['visible_to_client']) {
            $notesMessageClient .= "<li>" . $decodedNote . "</li>";
        }
    }

    $tech_cc_emails = [];
    $client_cc_emails = [];
    $tech_bcc_emails = [];
    $client_bcc_emails = [];

    // Only add all cc/bcc emails if we're sending them
    if ($send_cc_bcc_emails) {
        foreach ($valid_cc_emails as $email) {
            $email_username = username_from_email_address($email);
            if (user_is_tech($email_username))
                $tech_cc_emails[] = $email;
            else
                $client_cc_emails[] = $email;
        }


        foreach ($valid_bcc_emails as $email) {
            $email_username = username_from_email_address($email);
            if (user_is_tech($email_username))
                $tech_bcc_emails[] = $email;
            else
                $client_bcc_emails[] = $email;
        }
    }


    // Send emails if the user checked the send_emails checkbox or if it's forced
    if ($send_client_email || $send_cc_bcc_emails) {
        log_app(LOG_INFO, "Sending email on ticket update");
        if ($updatedStatus == "resolved") {
            $subject_status = "Resolved";
            $template_path = "ticket_resolved";
            $msg = "Ticket resolved successfully. An email was sent to the client, CC and BCC emails.";
        } else {
            if ($send_client_email && !$send_cc_bcc_emails) {
                $subject_status = "Updated";
                $template_path = "ticket_updated";
                $msg = "Ticket updated successfully. An email was sent to the client.";
            } else if (!$send_client_email && $send_cc_bcc_emails) {
                $subject_status = "Updated";
                $template_path = "ticket_updated";
                $msg = "Ticket updated successfully. An email was sent to the CC and BCC emails.";
            } else {
                $subject_status = "Updated";
                $template_path = "ticket_updated";
                $msg = "Ticket updated successfully. An email was sent to the client, CC and BCC emails.";
            }
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

        $template_client = new Template(from_root("/includes/templates/{$template_path}_client.phtml"));

        $template_client->client = $client_name["firstname"] . " " . $client_name["lastname"];
        $template_client->location = $location_name;
        $template_client->ticket_id = $ticket_id;
        $template_client->notes_message = $notesMessageClient;
        $template_client->site_url = getenv('ROOTDOMAIN');
        $template_client->description = html_entity_decode($updatedDescription);

        $select_attachments_query = "SELECT attachment_path from help.tickets WHERE id = ?";
        $stmt = mysqli_prepare($database, $select_attachments_query);
        mysqli_stmt_bind_param($stmt, "i", $ticket_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        //log_app(LOG_INFO, var_dump($result));
        if (!$result) {
            log_app(LOG_ERR, "Failed to get old attachment_path");
        }
        mysqli_stmt_close($stmt);

        $attachment_paths = explode(',', $result["attachment_path"]);

        if (isset($old_assigned_email))
            $tech_cc_emails[] = $old_assigned_email;

        $email_tech_res = send_email_and_add_to_ticket($ticket_id, $assigned_tech_email, $ticket_subject, $template_tech, $tech_cc_emails, $tech_bcc_emails, $attachment_paths);


        if (($client_email == $assigned_tech_email) || !$send_client_email) {
            $email_client_res = send_email_and_add_to_ticket($ticket_id, getenv("GMAIL_USER"), $ticket_subject, $template_client, $client_cc_emails, $client_bcc_emails, $attachment_paths);
        } else {
            $email_client_res = send_email_and_add_to_ticket($ticket_id, $client_email, $ticket_subject, $template_client, $client_cc_emails, $client_bcc_emails, $attachment_paths);
        }


        //function logTicketChange($database, $ticket_id, $updatedby, $field_name, $old_value, $new_value)
        if (!$email_tech_res || !$email_client_res) {
            if ($send_client_email && !$send_cc_bcc_emails) {
                $subject_status = "Updated";
                $template_path = "ticket_updated";
                $error = "Error sending email to assigned tech and the client";
            } else if (!$send_client_email && $send_cc_bcc_emails) {
                $subject_status = "Updated";
                $template_path = "ticket_updated";
                $error = "Error sending email to assigned tech and CC/BCC emails ";
            } else {
                $subject_status = "Updated";
                $template_path = "ticket_updated";
                $error = "Error sending email to assigned tech, client, and CC/BCC emails";
            }

            $_SESSION['current_status'] = $error;
            $_SESSION['status_type'] = 'error';

            $formData = http_build_query($_POST);
            log_app(LOG_ERR, "$error \n\n $formData");
            header("Location: edit_ticket.php?$formData&id=$ticket_id");
            exit;
        }

        $sent_tech_email_log_msg = "Sent tech-privileged emails to: $assigned_tech_email, ";
        $sent_client_email_log_msg = "Sent non-tech emails to: $client_email, ";

        foreach ($tech_cc_emails as $tech_cc_email) {
            if ($assigned_tech_email != $tech_cc_email)
                $sent_tech_email_log_msg .= "$tech_cc_email, ";
        }

        foreach ($tech_bcc_emails as $tech_bcc_email) {
            if ($assigned_tech_email != $tech_bcc_email)
                $sent_tech_email_log_msg .= "$tech_bcc_email, ";
        }

        foreach ($client_cc_emails as $client_cc_email) {
            $sent_client_email_log_msg .= "$client_cc_email, ";
        }

        foreach ($client_bcc_emails as $client_bcc_email) {
            $sent_client_email_log_msg .= "$client_bcc_email, ";
        }

        logTicketChange($database, $ticket_id, $_SESSION["username"], "sent_emails", "N/A", $sent_tech_email_log_msg." ".$sent_client_email_log_msg);
    }

    
    $_SESSION['current_status'] = $msg;
    $_SESSION['status_type'] = "success";

    // Redirect to the same page after successful update
    header('Location: edit_ticket.php?id=' . $ticket_id);
    exit;
}
