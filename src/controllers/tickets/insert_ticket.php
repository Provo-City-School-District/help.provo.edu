<?php
require_once('../../includes/init.php');
require_once('../../includes/helpdbconnect.php');

include("ticket_utils.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['can_create_tickets'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to Create tickets.';
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate user inputs
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_SPECIAL_CHARS);
    $room = filter_input(INPUT_POST, 'room', FILTER_SANITIZE_SPECIAL_CHARS);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
    $client = $_POST['client'];
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
    $cc_emails = filter_input(INPUT_POST, 'cc_emails', FILTER_SANITIZE_SPECIAL_CHARS);
    $bcc_emails = filter_input(INPUT_POST, 'bcc_emails', FILTER_SANITIZE_SPECIAL_CHARS);

    // Check if required fields are empty
    if (empty($location) || empty($room) || empty($name) || empty($description) || empty($phone)) {
        // Handle empty fields (e.g., show an error message)
        $error = 'All fields are required';
        $formData = http_build_query($_POST);
        $_SESSION['error_message'] = "All fields are required.";
        header("Location: create_ticket.php?error=$error&$formData");
        exit;
    }

    $valid_cc_emails = array();
    if (trim($cc_emails) !== "") {
        $valid_cc_emails = split_email_string_to_arr($cc_emails);
        if (!$valid_cc_emails) {
            $error = 'Error parsing CC emails (invalid format)';
            $formData = http_build_query($_POST);
            $_SESSION['error_message'] = "Error parsing CC emails (invalid format)";
            header("Location: create_ticket.php?error=$error&$formData");
            exit;
        }
    }

    $valid_bcc_emails = array();
    if (trim($bcc_emails) !== "") {
        $valid_bcc_emails = split_email_string_to_arr($bcc_emails);
        if (!$valid_bcc_emails) {
            $error = 'Error parsing BCC emails (invalid format)';
            $formData = http_build_query($_POST);
            $_SESSION['error_message'] = "Error parsing BCC emails (invalid format)";
            header("Location: create_ticket.php?error=$error&$formData");
            exit;
        }
    }

    // Handle file upload
    $uploadPaths = array();
    if (isset($_FILES['attachment'])) {
        $attachmentCount = count($_FILES['attachment']['name']);

        for ($i = 0; $i < $attachmentCount; $i++) {
            $filename = $_FILES['attachment']['name'][$i];
            $tmp_name = $_FILES['attachment']['tmp_name'][$i];
            $error = $_FILES['attachment']['error'][$i];

            if ($error === UPLOAD_ERR_OK) {
                // Create uploads directory if it doesn't exist
                if (!file_exists("uploads/")) {
                    mkdir("uploads/", 0777, true);
                }

                // Generate a unique filename using the current timestamp and the original filename
                $uniqueFilename = date('Ymd_Hi') . '_' . $filename;
                $uploadPath = "uploads/{$uniqueFilename}";
                move_uploaded_file($tmp_name, $uploadPath);
                $uploadPaths[] = $uploadPath;
            }
        }
    }

    // Create an SQL INSERT query
    $insertQuery = "INSERT INTO tickets (location, room, name, description, created, last_updated, due_date, status, client,attachment_path,phone,cc_emails,bcc_emails)
                VALUES (?, ?, ?, ?, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 10 DAY), 'open', ?, ?, ?, ?, ?)";

    // Prepare the SQL statement
    $stmt = mysqli_prepare($database, $insertQuery);

    if ($stmt === false) {
        die('Error preparing insert query: ' . mysqli_error($database));
    }
    $uploadPath = array();
    foreach ($uploadPaths as $attachmentPath) {
        $uploadPath[] = $attachmentPath;
    }
    $attachmentPath = implode(',', $uploadPath);
    // print_r($uploadPath);
    // Bind parameters
    $cc_emails_clean = implode(',', $valid_cc_emails);
    $bcc_emails_clean = implode(',', $valid_bcc_emails);
    mysqli_stmt_bind_param($stmt, 'sssssssss', $location, $room, $name, $description, 
        $client, $attachmentPath, $phone, $cc_emails_clean, $bcc_emails_clean);


    // Execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        // After successfully inserting the ticket, set a success message;
        $_SESSION['success_message'] = "Ticket created successfully.";

        // After successfully inserting the ticket, fetch the ID of the new ticket
        $ticketId = mysqli_insert_id($database);

        // Redirect to the edit page for the new ticket
        header('Location: edit_ticket.php?id=' . $ticketId);
        exit;
    } else {
        // Handle insert error (e.g., show an error message)
        $error = 'Error creating ticket';
        $formData = http_build_query($_POST);
        $_SESSION['error_message'] = "Error creating ticket.";
        header("Location: create_ticket.php?error=$error&$formData");
        exit;
    }
}
