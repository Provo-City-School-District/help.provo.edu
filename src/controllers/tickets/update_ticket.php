<?php
require_once('../../includes/init.php');
require_once('../../includes/helpdbconnect.php');

include("ticket_utils.php");

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

    $valid_cc_emails = array();
    if (trim($updatedCCEmails) !== "") {
        $valid_cc_emails = split_email_string_to_arr($updatedCCEmails);
        if (!$valid_cc_emails) {
            $error = 'Error parsing CC emails (invalid format)';
            $formData = http_build_query($_POST);
            $_SESSION['error_message'] = "Error parsing CC emails (invalid format)";
            header("Location: edit_ticket.php?error=$error&$formData&id=$ticket_id");
            exit;
        }
    }

    $valid_bcc_emails = array();
    if (trim($updatedBCCEmails) !== "") {
        $valid_bcc_emails = split_email_string_to_arr($updatedBCCEmails);
        if (!$valid_bcc_emails) {
            $error = 'Error parsing BCC emails (invalid format)';
            $formData = http_build_query($_POST);
            $_SESSION['error_message'] = "Error parsing BCC emails (invalid format)";
            header("Location: edit_ticket.php?error=$error&$formData&id=$ticket_id");
            exit;
        }
    }


    // TODO: Make sure client gets emailed
    if ($updatedStatus == "resolved") {
        
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
        bcc_emails = '$bcc_emails_clean'
        WHERE id = $ticket_id";

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

    // Log the ticket changes
    $log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at) VALUES (?, ?, ?, ?, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))";
    $log_stmt = mysqli_prepare($database, $log_query);
    if ($old_ticket_data['client'] != $updatedClient) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $clientColumn, $old_ticket_data['client'], $updatedClient);
        mysqli_stmt_execute($log_stmt);
    }
    if ($old_ticket_data['employee'] != $updatedEmployee) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $employeeColumn, $old_ticket_data['employee'], $updatedEmployee);
        mysqli_stmt_execute($log_stmt);
    }
    if ($old_ticket_data['location'] != $updatedLocation) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $locationColumn, $old_ticket_data['location'], $updatedLocation);
        mysqli_stmt_execute($log_stmt);
    }
    if ($old_ticket_data['room'] != $updatedRoom) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $roomColumn, $old_ticket_data['room'], $updatedRoom);
        mysqli_stmt_execute($log_stmt);
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
    }
    if ($old_ticket_data['status'] != $updatedStatus) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $statusColumn, $old_ticket_data['status'], $updatedStatus);
        mysqli_stmt_execute($log_stmt);
    }
    if ($old_ticket_data['phone'] != $updatedPhone) {
        mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $updatedby, $phoneColumn, $old_ticket_data['phone'], $updatedPhone);
        mysqli_stmt_execute($log_stmt);
    }

    // After successfully updating the ticket, set a success message;
    $_SESSION['success_message'] = "Ticket updated successfully.";
    // Redirect to the same page after successful update
    header('Location: edit_ticket.php?id=' . $ticket_id);
    exit;
}
