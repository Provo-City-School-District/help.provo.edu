<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');

require("ticket_utils.php");
require("email_utils.php");
require("template.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
    $updatedBCCEmails = filter_input(INPUT_POST, 'bcc_emails', FILTER_SANITIZE_SPECIAL_CHARS);
    $updatedRequestType = trim(htmlspecialchars($_POST['request_type']));
    $updatedPriority = trim(htmlspecialchars($_POST['priority']));
    $updatedParentTicket = intval(trim(htmlspecialchars($_POST['parent_ticket'])));
    $changesMessage = "";
    $created_at = trim(htmlspecialchars($_POST['ticket_create_date']));
    $forceEmails = false;

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
    $updateTicketQuery = "UPDATE tickets SET
        client = '$updatedClient',
        employee = '$updatedEmployee',
        location = '$updatedLocation',
        room = '$updatedRoom',
        name = '$updatedName',
        description = '$updatedDescription',
        due_date = '$updatedDueDate',
        status = '$updatedStatus',
        phone = '$updatedPhone',
        cc_emails = '$updatedCCEmails',
        bcc_emails = '$updatedBCCEmails',
        priority = '$updatedPriority',
        request_type_id = '$updatedRequestType',
        parent_ticket = '$updatedParentTicket',
        last_updated = NOW()
        WHERE id = '$ticket_id'";

    // Execute the update queries
    $updateTicketResult = mysqli_query($database, $updateTicketQuery);

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
    $log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at) VALUES (?, ?, ?, ?, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))";
    $log_stmt = mysqli_prepare($database, $log_query);
    if (isset($old_ticket_data['priority'], $updatedPriority) && $old_ticket_data['priority'] != $updatedPriority) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $priorityColumn, $old_ticket_data['priority'], $updatedPriority);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Priority from " . $priorityTypes[$old_ticket_data['priority']] . " to " . $priorityTypes[$updatedPriority] . "</li>";
    }

    if (isset($old_ticket_data['request_type_id'], $updatedRequestType) && $old_ticket_data['request_type_id'] != $updatedRequestType) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $requestTypeColumn, $old_ticket_data['request_type_id'], $updatedRequestType);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Request Type from " . $old_ticket_data['request_type_id'] . " to " . $updatedRequestType . "</li>";
    }

    if (isset($old_ticket_data['client'], $updatedClient) && $old_ticket_data['client'] != $updatedClient) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $clientColumn, $old_ticket_data['client'], $updatedClient);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Client from " . $old_ticket_data['client'] . " to " . $updatedClient . "</li>";
        $old_client = email_address_from_username($old_ticket_data['client']);
        //add old client to cc emails array so that they get an email about the ticket getting client changed
        array_push($valid_cc_emails, $old_client);
        $forceEmails = true;
    }

    if (isset($old_ticket_data['employee'], $updatedEmployee) && $old_ticket_data['employee'] != $updatedEmployee) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $employeeColumn, $old_ticket_data['employee'], $updatedEmployee);
        mysqli_stmt_execute($log_stmt);

        if ($old_ticket_data['employee'] !== null && $old_ticket_data['employee'] !== 'unassigned') {
            $old_assigned = email_address_from_username($old_ticket_data['employee']);
            $valid_cc_emails[] = $old_assigned;
        } else {
            $old_ticket_data['employee'] = "unassigned";
        }
        $changesMessage .= "<li>Changed Employee from " . $old_ticket_data['employee'] . " to " . $updatedEmployee . "</li>";
        $forceEmails = true;
    }

    if (isset($old_ticket_data['location'], $updatedLocation) && $old_ticket_data['location'] != $updatedLocation) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $locationColumn, $old_ticket_data['location'], $updatedLocation);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Location from " . $old_ticket_data['location'] . " to " . $updatedLocation . "</li>";
    }

    if (isset($old_ticket_data['room'], $updatedRoom) && $old_ticket_data['room'] != $updatedRoom) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $roomColumn, $old_ticket_data['room'], $updatedRoom);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Room from " . $old_ticket_data['room'] . " to " . $updatedRoom . "</li>";
    }

    if (isset($old_ticket_data['name'], $updatedName) && $old_ticket_data['name'] != $updatedName) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $nameColumn, $old_ticket_data['name'], $updatedName);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Subject from " . $old_ticket_data['name'] . " to " . $updatedName . "</li>";
    }

    if (isset($old_ticket_data['description'], $updatedDescription) && $old_ticket_data['description'] != $updatedDescription) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $descriptionColumn, $old_ticket_data['description'], $updatedDescription);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Description from " . html_entity_decode($old_ticket_data['description']) . " to " . html_entity_decode($updatedDescription) . "</li>";
    }

    if (isset($old_ticket_data['due_date'], $updatedDueDate) && $old_ticket_data['due_date'] != $updatedDueDate) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $dueDateColumn, $old_ticket_data['due_date'], $updatedDueDate);
        mysqli_stmt_execute($log_stmt);
        removeAlert($database, $pastDueMessage, $ticket_id);
        $changesMessage .= "<li>Changed Due Date from " . $old_ticket_data['due_date'] . " to " . $updatedDueDate . "</li>";
    }

    if (isset($old_ticket_data['status'], $updatedStatus) && $old_ticket_data['status'] != $updatedStatus) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $statusColumn, $old_ticket_data['status'], $updatedStatus);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Status from " . $statusTypes[$old_ticket_data['status']] . " to " . $statusTypes[$updatedStatus] . "</li>";
        if ($updatedStatus == "resolved") {
            removeAlert($database, $pastDueMessage, $ticket_id);
        }
    }

    if (isset($old_ticket_data['phone'], $updatedPhone) && $old_ticket_data['phone'] != $updatedPhone) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $phoneColumn, $old_ticket_data['phone'], $updatedPhone);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Phone from " . $old_ticket_data['phone'] . " to " . $updatedPhone . "</li>";
    }

    if (isset($old_ticket_data['cc_emails'], $updatedCCEmails) && $old_ticket_data['cc_emails'] != $updatedCCEmails) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $ccEmailsColumn, $old_ticket_data['cc_emails'], $updatedCCEmails);
        mysqli_stmt_execute($log_stmt);
        if ($old_ticket_data['cc_emails'] == "") {
            $changesMessage .= "<li>Added CC Emails " . $updatedCCEmails . "</li>";
        } else {
            $changesMessage .= "<li>Changed CC Emails from " . $old_ticket_data['cc_emails'] . " to " . $updatedCCEmails . "</li>";
        }
    }

    if (isset($old_ticket_data['bcc_emails'], $updatedBCCEmails) && $old_ticket_data['bcc_emails'] != $updatedBCCEmails) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $bccEmailsColumn, $old_ticket_data['bcc_emails'], $updatedBCCEmails);
        mysqli_stmt_execute($log_stmt);
        if ($old_ticket_data['bcc_emails']) {
            $changesMessage .= "<li>Added BCC Emails " . $updatedBCCEmails . "</li>";
        } else {
            $changesMessage .= "<li>Changed BCC Emails from " . $old_ticket_data['bcc_emails'] . " to " . $updatedBCCEmails . "</li>";
        }
    }

    if (isset($old_ticket_data['parent_ticket'], $updatedParentTicket) && $old_ticket_data['parent_ticket'] != $updatedParentTicket) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $parentTicketColumn, $old_ticket_data['parent_ticket'], $updatedParentTicket);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Parent Ticket from " . $old_ticket_data['parent_ticket'] . " to " . $updatedParentTicket . "</li>";
    }


    // Check if the ticket has an alert about not being updated in last 48 hours and clear it since the ticket was just updated.
    removeAlert($database, $alert48Message, $ticket_id);
    removeAlert($database, $alert7DayMessage, $ticket_id);

    // Send emails if the user checked the send_emails checkbox
    $sendEmails = isset($_POST['send_emails']) && ($_POST['send_emails'] == "send_emails");

    // After successfully updating the ticket, set a success message;
    $msg = "Ticket updated successfully.";

    // Get the last 3 notes for the ticket
    $notes = get_ticket_notes($ticket_id, 3);
    $notesMessageClient = "";
    $notesMessageTech = "";

    foreach ($notes as $note) {
        $noteCreator = $note['creator'];
        $decodedNote = $noteCreator . ": " . htmlspecialchars_decode($note['note']);
        $notesMessageTech .= "<li>" . $decodedNote . "</li>";
        if ($note['visible_to_client']) {
            $notesMessageClient .= "<li>" . $decodedNote . "</li>";
        }
    }

    // Eventually use notesMessageClient to send a unique email to clients without tech notes
    $tech_cc_emails = [];
    $client_cc_emails = [];
    foreach ($valid_cc_emails as $email) {
        $email_username = username_from_email_address($email);
        if (user_is_tech($email_username))
            $tech_cc_emails[] = $email;
        else
            $client_cc_emails[] = $email;
    }

    $tech_bcc_emails = [];
    $client_bcc_emails = [];
    foreach ($valid_bcc_emails as $email) {
        $email_username = username_from_email_address($email);
        if (user_is_tech($email_username))
            $tech_bcc_emails[] = $email;
        else
            $client_bcc_emails[] = $email;
    }

    // Send emails if the user checked the send_emails checkbox
    if ($sendEmails || $forceEmails || $updatedStatus == "pending" || $updatedStatus == "resolved") {
        $subject_status = "Updated";
        $template_path = "ticket_updated";
        
        if ($updatedStatus == "resolved") {
            $subject_status = "Resolved";
            $template_path = "ticket_resolved";
        }

        // message for gui to display
        $msg = "Ticket updated successfully. An email was sent to the client, CC and BCC emails.";
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

        $email_tech_res = send_email_and_add_to_ticket($ticket_id, $assigned_tech_email, $ticket_subject, $template_tech, $tech_cc_emails, $tech_bcc_emails);

        if ($client_email == $assigned_tech_email)
            $email_client_res = send_email_and_add_to_ticket($ticket_id, getenv("GMAIL_USER"), $ticket_subject, $template_client, $client_cc_emails, $client_bcc_emails);
        else
            $email_client_res = send_email_and_add_to_ticket($ticket_id, $client_email, $ticket_subject, $template_client, $client_cc_emails, $client_bcc_emails);

        if (!($email_tech_res && $email_client_res)) {
            $error = 'Error sending email to assigned tech and CC/BCC';
            $formData = http_build_query($_POST);
            $_SESSION['current_status'] = $error;
            $_SESSION['status_type'] = 'error';
            log_app(LOG_ERR, "$error \n\n $formData");
            header("Location: edit_ticket.php?$formData&id=$ticket_id");
            exit;
        }
    }

    $_SESSION['current_status'] = $msg;
    $_SESSION['status_type'] = "success";

    // Redirect to the same page after successful update
    header('Location: edit_ticket.php?id=' . $ticket_id);
    exit;
}
