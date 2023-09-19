<?php
require_once('../../includes/init.php');
require_once('../../includes/helpdbconnect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate user inputs
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_SPECIAL_CHARS);
    $room = filter_input(INPUT_POST, 'room', FILTER_SANITIZE_SPECIAL_CHARS);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
    $client = $_POST['client'];

    // Check if required fields are empty
    if (empty($location) || empty($room) || empty($name) || empty($description)) {
        // Handle empty fields (e.g., show an error message)
        die('All fields are required');
    }

    // Create an SQL INSERT query
    $insertQuery = "INSERT INTO tickets (location, room, name, description, created, last_updated, due_date, status, client)
                VALUES (?, ?, ?, ?, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 10 DAY), 'open', ?)";

    // Prepare the SQL statement
    $stmt = mysqli_prepare($database, $insertQuery);

    if ($stmt === false) {
        die('Error preparing insert query: ' . mysqli_error($database));
    }

    // Bind parameters
    mysqli_stmt_bind_param($stmt, 'sssss', $location, $room, $name, $description, $client);

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
        // Handle the error (e.g., show an error message)
        die('Error creating ticket: ' . mysqli_error($database));
    }
}
?>