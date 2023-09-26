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

// Check if any files were uploaded
if (isset($_FILES['attachment'])) {
    // Loop through the uploaded files
    for ($i = 0; $i < count($_FILES['attachment']['name']); $i++) {
        // Get the file name and temporary file path
        $fileName = $_FILES['attachment']['name'][$i];
        $tmpFilePath = $_FILES['attachment']['tmp_name'][$i];

        // Check if the file was uploaded successfully
        if ($tmpFilePath != "") {
            // Generate a unique file name
            $newFilePath = "uploads/" . $ticket_id . "-" . $fileName;

            // Move the file to the uploads directory
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                // File was uploaded successfully, insert the file path into the database
                require_once('../../includes/helpdbconnect.php');

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
        }
    }
}

// Redirect back to the ticket
header("Location: edit_ticket.php?id=$ticket_id");
exit;