<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');
require_once('email_utils.php');
require_once('template.php');

include("ticket_utils.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['can_create_tickets'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to Create tickets.';
        exit;
    }
}
$username = $_SESSION["username"];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate user inputs
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_SPECIAL_CHARS);
    $room = filter_input(INPUT_POST, 'room', FILTER_SANITIZE_SPECIAL_CHARS);
    $name = filter_input(INPUT_POST, 'ticket_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
    $client = $_POST['client'];
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
    $cc_emails = filter_input(INPUT_POST, 'cc_emails', FILTER_SANITIZE_SPECIAL_CHARS);
    $bcc_emails = filter_input(INPUT_POST, 'bcc_emails', FILTER_SANITIZE_SPECIAL_CHARS);
    $assigned_tech = filter_input(INPUT_POST, 'assigned', FILTER_SANITIZE_SPECIAL_CHARS);

    // standard by default
    $priority = 10; // filter_input(INPUT_POST, 'priority', FILTER_SANITIZE_SPECIAL_CHARS);

    if (intval($priority) <= 0) {
        $error = 'Error parsing priority';
        $formData = http_build_query($_POST);
        $_SESSION['current_status'] = $error;
        $_SESSION['status_type'] = 'error';
        header("Location: create_ticket.php?$formData");
        exit;
    }

    // Check if required fields are empty
    if (empty($location) || empty($name) || empty($description)) {
        // Handle empty fields (e.g., show an error message)
        $error = 'All fields are required';
        $formData = http_build_query($_POST);
        $_SESSION['current_status'] = $error;
        $_SESSION['status_type'] = 'error';

        header("Location: create_ticket.php?$formData");
        exit;
    }

    if ((empty($room) || empty($phone)) && !$_SESSION["permissions"]["is_tech"]) {
        // Handle empty fields (e.g., show an error message)
        $error = 'All fields are required';
        $formData = http_build_query($_POST);
        $_SESSION['current_status'] = $error;
        $_SESSION['status_type'] = 'error';

        header("Location: create_ticket.php?$formData");
        exit;
    }

    $valid_cc_emails = [];
    if (trim($cc_emails) !== "") {
        $valid_cc_emails = split_email_string_to_arr($cc_emails);
        if (!$valid_cc_emails) {
            $error = 'Error parsing CC emails (invalid format)';
            $formData = http_build_query($_POST);
            $_SESSION['current_status'] = $error;
            $_SESSION['status_type'] = 'error';

            header("Location: create_ticket.php?$formData");
            exit;
        }
    }

    $valid_bcc_emails = [];
    if (trim($bcc_emails) !== "") {
        $valid_bcc_emails = split_email_string_to_arr($bcc_emails);
        if (!$valid_bcc_emails) {
            $error = 'Error parsing BCC emails (invalid format)';
            $formData = http_build_query($_POST);
            $_SESSION['current_status'] = $error;
            $_SESSION['status_type'] = 'error';
            header("Location: create_ticket.php?$formData");
            exit;
        }
    }

    // Handle file upload
    $uploadPaths = [];
    if (isset($_FILES['attachment'])) {
        $attachmentCount = count($_FILES['attachment']['name']);

        for ($i = 0; $i < $attachmentCount; $i++) {
            $filename = $_FILES['attachment']['name'][$i];
            $tmp_name = $_FILES['attachment']['tmp_name'][$i];
            $error = $_FILES['attachment']['error'][$i];

            if ($error === UPLOAD_ERR_OK) {
                // // Create uploads directory if it doesn't exist
                // if (!file_exists("../../uploads/")) {
                //     mkdir("../../uploads/", 0766, true);
                // }

                // Generate a unique filename using the current timestamp and the original filename
                $uniqueFilename = date('Ymd_Hi') . '_' . $filename;
                $uploadPath = "../../uploads/{$uniqueFilename}";
                move_uploaded_file($tmp_name, $uploadPath);
                $uploadPaths[] = $uploadPath;
            }
        }
    }


    if (isset($assigned_tech)) {
        $usernamesResult = $database->execute_query("SELECT username,is_tech FROM users WHERE is_tech = 1");

        if (!$usernamesResult) {
            die('Error fetching usernames: ' . mysqli_error($database));
        }

        // Store the usernames in an array
        $techusernames = [];
        while ($usernameRow = mysqli_fetch_assoc($usernamesResult)) {
            if ($usernameRow['is_tech'] == 1) {
                $techusernames[] = $usernameRow['username'];
            }
        }

        if ($assigned_tech != "unassigned" && !in_array($assigned_tech, $techusernames)) {
            log_app(LOG_ERR, "Assigned tech was not an actual tech. Aborting ticket creation...");
            die;
        }
    }


    // Create an SQL INSERT query
    $insertQuery = "INSERT INTO tickets (location, room, name, description, created, last_updated, due_date, status, client,attachment_path,phone,cc_emails,bcc_emails,request_type_id,priority,employee)
                VALUES (?, ?, ?, ?, ?, ?, ?,'open', ?, ?, ?, ?, ?,0,10,?)";

    // Prepare the SQL statement
    $stmt = mysqli_prepare($database, $insertQuery);

    if ($stmt === false) {
        die('Error preparing insert query: ' . mysqli_error($database));
    }
    $uploadPath = [];
    foreach ($uploadPaths as $attachmentPath) {
        // Replace '../../uploads/' with '/uploads/' in the attachment path if it exists
        $attachmentPath = str_replace('../../uploads/', '/uploads/', $attachmentPath);
        $uploadPath[] = $attachmentPath;
    }
    $attachmentPath = implode(',', $uploadPath);
    // print_r($uploadPath);
    // Bind parameters
    $cc_emails_clean = implode(',', $valid_cc_emails);
    $bcc_emails_clean = implode(',', $valid_bcc_emails);

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
        'sssssssssssss',
        $location,
        $room,
        $name,
        $description,
        $created_time,
        $created_time,
        $due_date,
        $client,
        $attachmentPath,
        $phone,
        $cc_emails_clean,
        $bcc_emails_clean,
        $assigned_tech
    );


    // Execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        // After successfully inserting the ticket, set a success message;
        $_SESSION['current_status'] = "Ticket created successfully.";
        $_SESSION['status_type'] = "success";
        // After successfully inserting the ticket, fetch the ID of the new ticket
        $ticketId = mysqli_insert_id($database);
        $template = new Template(from_root("/includes/templates/ticket_creation_receipt.phtml"));
        $template->ticket_id = $ticketId;
        $template->site_url = getenv('ROOTDOMAIN');
        $template->description = html_entity_decode($description);

        $receipt_subject = "Ticket $ticketId - $name";
        send_email(email_address_from_username($username), $receipt_subject, $template);

        // Redirect to the edit page for the new ticket
        header("Location: edit_ticket.php?id=$ticketId");
        exit;
    } else {
        // Handle insert error (e.g., show an error message)
        $error = 'Error creating ticket';
        $formData = http_build_query($_POST);
        $_SESSION['current_status'] = "Error creating ticket.";
        $_SESSION['status_type'] = 'error';

        header("Location: create_ticket.php?$formData");
        exit;
    }
}
