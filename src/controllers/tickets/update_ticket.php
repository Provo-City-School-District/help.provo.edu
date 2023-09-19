<?php
require_once('../../includes/init.php');
require_once('../../includes/helpdbconnect.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle the form submission to update the ticket information
    // Retrieve updated values from the form
    $ticket_id = $_POST['ticket_id'];
    $updatedClient = $_POST['client'];
    $updatedEmployee = $_POST['employee'];
    $updatedLocation = $_POST['location'];
    $updatedRoom = $_POST['room'];
    $updatedName = $_POST['name'];
    $updatedDescription = $_POST['description'];
    $updatedDueDate = $_POST['due_date'];
    $updatedStatus = $_POST['status'];

    // Perform SQL UPDATE queries to update the ticket and notes
    // Example (you'll need to modify and expand this based on your database structure):
    $updateTicketQuery = "UPDATE tickets SET
        client = '$updatedClient',
        employee = '$updatedEmployee',
        location = '$updatedLocation',
        room = '$updatedRoom',
        name = '$updatedName',
        description = '$updatedDescription',
        due_date = '$updatedDueDate',
        status = '$updatedStatus'
        WHERE id = $ticket_id";

    // Execute the update queries
    $updateTicketResult = mysqli_query($database, $updateTicketQuery);

    if (!$updateTicketResult) {
        die('Error updating ticket: ' . mysqli_error($database));
    }
    // After successfully updating the ticket, set a success message;
    $_SESSION['success_message'] = "Ticket updated successfully.";
   // Redirect to the same page after successful update
   header('Location: edit_ticket.php?id=' . $ticket_id);
   exit;
}
print_r($_SESSION);
?>