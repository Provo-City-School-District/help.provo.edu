<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');

require("ticket_utils.php");
require("email_utils.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle the form submission to update the ticket information
    // Retrieve updated values from the form
    $ticket_id = trim(htmlspecialchars($_POST['ticket_id']));
    $updatedClient = trim(htmlspecialchars($_POST['client']));
    $updatedEmployee = trim(htmlspecialchars($_POST['employee']));
    $updatedLocation = trim(htmlspecialchars($_POST['location']));
    $updatedRoom = trim(htmlspecialchars($_POST['room']));
    $updatedName = trim(htmlspecialchars($_POST['name']));
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
    $forceEmails = false;

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

    // Repack emails
    $cc_emails_clean = implode(',', $valid_cc_emails);
    $bcc_emails_clean = implode(',', $valid_bcc_emails);

    // Perform SQL UPDATE queries to update the ticket and notes
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
        cc_emails = '$cc_emails_clean',
        bcc_emails = '$bcc_emails_clean',
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

    // Log the ticket changes
    $log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at) VALUES (?, ?, ?, ?, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))";
    $log_stmt = mysqli_prepare($database, $log_query);
    if ($old_ticket_data['priority'] != $updatedPriority) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $priorityColumn, $old_ticket_data['priority'], $updatedPriority);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Priority from " . $priorityTypes[$old_ticket_data['priority']] . " to " . $priorityTypes[$updatedPriority] . "</li>";
    }

    if ($old_ticket_data['request_type_id'] != $updatedRequestType) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $requestTypeColumn, $old_ticket_data['request_type_id'], $updatedRequestType);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Request Type from " . $old_ticket_data['request_type_id'] . " to " . $updatedRequestType . "</li>";
    }
    if ($old_ticket_data['client'] != $updatedClient) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $clientColumn, $old_ticket_data['client'], $updatedClient);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Client from " . $old_ticket_data['client'] . " to " . $updatedClient . "</li>";
        $old_client = email_address_from_username($old_ticket_data['client']);
        //add old client to cc emails array so that they get an email about the ticket getting client changed
        array_push($valid_cc_emails, $old_client);
        $forceEmails = true;
    }
    if ($old_ticket_data['employee'] != $updatedEmployee) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $employeeColumn, $old_ticket_data['employee'], $updatedEmployee);
        mysqli_stmt_execute($log_stmt);
        $old_assigned = email_address_from_username($old_ticket_data['employee']);
        $new_assigned = email_address_from_username($updatedEmployee);
        $changesMessage .= "<li>Changed Employee from " . $old_ticket_data['employee'] . " to " . $updatedEmployee . "</li>";
        //force send email to both old and new assigned employee
        array_push($valid_cc_emails, $old_assigned);
        array_push($valid_cc_emails, $new_assigned);
        $forceEmails = true;
    }
    if ($old_ticket_data['location'] != $updatedLocation) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $locationColumn, $old_ticket_data['location'], $updatedLocation);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Location from " . $old_ticket_data['location'] . " to " . $updatedLocation . "</li>";
    }
    if ($old_ticket_data['room'] != $updatedRoom) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $roomColumn, $old_ticket_data['room'], $updatedRoom);
        mysqli_stmt_execute($log_stmt);
        $changesMessage .= "<li>Changed Room from " . $old_ticket_data['room'] . " to " . $updatedRoom . "</li>";
    }
    if ($old_ticket_data['name'] != $updatedName) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $nameColumn, $old_ticket_data['name'], $updatedName);
        mysqli_stmt_execute($log_stmt);
    }
    if ($old_ticket_data['description'] != $updatedDescription) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $descriptionColumn, $old_ticket_data['description'], $updatedDescription);
        mysqli_stmt_execute($log_stmt);
    }
    if ($old_ticket_data['due_date'] != $updatedDueDate) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $dueDateColumn, $old_ticket_data['due_date'], $updatedDueDate);
        mysqli_stmt_execute($log_stmt);
        removeAlert($database, $pastDueMessage, $ticket_id);
    }
    if ($old_ticket_data['status'] != $updatedStatus) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $statusColumn, $old_ticket_data['status'], $updatedStatus);
        mysqli_stmt_execute($log_stmt);
    }
    if ($old_ticket_data['phone'] != $updatedPhone) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $phoneColumn, $old_ticket_data['phone'], $updatedPhone);
        mysqli_stmt_execute($log_stmt);
    }
    if ($old_ticket_data['cc_emails'] != $updatedCCEmails) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $ccEmailsColumn, $old_ticket_data['cc_emails'], $updatedCCEmails);
        mysqli_stmt_execute($log_stmt);
    }
    if ($old_ticket_data['bcc_emails'] != $updatedBCCEmails) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $bccEmailsColumn, $old_ticket_data['bcc_emails'], $updatedBCCEmails);
        mysqli_stmt_execute($log_stmt);
    }
    if ($old_ticket_data['parent_ticket'] != $updatedParentTicket) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $parentTicketColumn, $old_ticket_data['parent_ticket'], $updatedParentTicket);
        mysqli_stmt_execute($log_stmt);
    }

    // Check if the ticket has an alert about not being updated in last 48 hours and clear it since the ticket was just updated.
    removeAlert($database, $alert48Message, $ticket_id);

    // Send emails if the user checked the send_emails checkbox
    $sendEmails = isset($_POST['send_emails']) && ($_POST['send_emails'] == "send_emails");

    // After successfully updating the ticket, set a success message;
    $msg = "Ticket updated successfully.";


    if ($sendEmails || $forceEmails) {
        // message for gui to display
        $msg = "Ticket updated successfully. An email was sent to the client, CC and BCC emails.";
        $client_email = email_address_from_username($updatedClient) . "," . email_address_from_username($updatedEmployee);
        $ticket_subject = "Ticket " . $ticket_id . " (Updated)";

        $ticket_body = "";
        if ($updatedStatus == "resolved") {
            $ticket_body = "Ticket " . $ticket_id . " has been resolved.";
        } else {
            $ticket_body = "Ticket " . $ticket_id . " has been updated <br> Changes Made: <ul>" . $changesMessage . "</ul>";
        }

        $email_res = send_email($client_email, $ticket_subject, $ticket_body, $valid_cc_emails, $valid_bcc_emails);
        if (!$email_res) {
            $error = 'Error sending email to client, CC and BCC';
            $formData = http_build_query($_POST);
            $_SESSION['current_status'] = $error;
            $_SESSION['status_type'] = 'error';
            header("Location: edit_ticket.php?$formData&id=$ticket_id");
            exit;
        }
    } else if ($updatedStatus == "resolved") {
        //message for gui to display
        $msg = "Ticket updated successfully. An email was sent to the client.";

        $client_email = email_address_from_username($updatedClient);
        $ticket_subject = "Ticket " . $ticket_id  . " (Resolved)";
        $ticket_body = "Ticket " . $ticket_id . " has been resolved.";
        $email_res = send_email($client_email, $ticket_subject, $ticket_body);
        if (!$email_res) {
            $error = 'Error sending email to client';
            $formData = http_build_query($_POST);
            $_SESSION['current_status'] = $error;
            $_SESSION['status_type'] = 'error';
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
