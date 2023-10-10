<?php
// Start the session
require_once('../../includes/init.php');
require_once('../../includes/helpdbconnect.php');
// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // User is not logged in, redirect to login page
    header('Location: ../../index.php');
    exit;
}

// Check if the ticket ID is set
if (!isset($_POST['ticket_id'])) {
    // Ticket ID is not set, redirect to tickets page
    header('Location: tickets.php');
    exit;
}

// Get the ticket ID and username from the POST data
$ticket_id = $_POST['ticket_id'];
$username = $_POST['username'];

// Define the allowed file extensions
$allowed_extensions = array('jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx','xls','xlsx','txt');

// Check if any files were uploaded
if (isset($_FILES['attachment'])) {
    // Loop through the uploaded files
    for ($i = 0; $i < count($_FILES['attachment']['name']); $i++) {
        // Get the file name and temporary file path
        $fileName = $_FILES['attachment']['name'][$i];
        $tmpFilePath = $_FILES['attachment']['tmp_name'][$i];

        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        // Check if the file was uploaded successfully and has an allowed extension
        if ($tmpFilePath != "" && in_array($fileExtension, $allowed_extensions)) {
            // Generate a unique file name
            $newFilePath = "uploads/" . $ticket_id . "-" . $fileName;

            // Move the file to the uploads directory
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                // File was uploaded successfully, insert the file path into the database

                $query = "UPDATE tickets SET attachment_path = CONCAT(attachment_path, ',', ?) WHERE id = ?";
                $stmt = mysqli_prepare($database, $query);
                mysqli_stmt_bind_param($stmt, "si", $newFilePath, $ticket_id);
                mysqli_stmt_execute($stmt);

                // Close the database connection
                mysqli_close($database);
            } else {
                // File upload failed, set an error message
                $_SESSION['error_message'] = "File upload failed.";
            }
        } else {
            // File type not allowed, set an error message
            $_SESSION['error_message'] = "File type not allowed.";
        }
    }
}

// Redirect back to the ticket
header("Location: edit_ticket.php?id=$ticket_id");
exit;
