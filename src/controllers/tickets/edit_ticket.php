<?php
include("../../includes/header.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['can_view_tickets'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to view tickets.';
        exit;
    }
}
require_once('../../includes/helpdbconnect.php');
// Get the ticket ID from $_GET
$ticket_id = $_GET['id'];

// Query the ticket by ID and all notes for that ID
$query = "SELECT
tickets.*,
JSON_ARRAYAGG(
    JSON_OBJECT(
        'note', notes.note,
        'created', notes.created,
        'creator', notes.creator,
        'time', notes.time
    )
) AS notes
FROM
tickets
LEFT JOIN
notes
ON
tickets.id = notes.linked_id
WHERE
tickets.id = $ticket_id
GROUP BY
tickets.id
";
$result = mysqli_query($database, $query);

// Check if the query was successful
if (!$result) {
    die('Error: ' . mysqli_error($database));
}

// Fetch the ticket and notes from the result set
$row = mysqli_fetch_assoc($result);
?>
<article id="ticketWrapper">
    <?php
    // Check if a success message is set
if (isset($_SESSION['success_message'])) {
    echo '<div class="success-message">' . $_SESSION['success_message'] . '</div>';
    
    // Unset the success message to clear it
    unset($_SESSION['success_message']);
}
    ?>
    <h1>Ticket #<?= $row['id'] ?></h1>
    <!-- Form for updating ticket information -->
    <form method="POST" action="update_ticket.php">
        <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
        <label for="client">Client:</label>
        <input type="text" id="client" name="client" value="<?= $row['client'] ?>"><br>

        <label for="employee">Assigned Tech:</label>
        <input type="text" id="employee" name="employee" value="<?= $row['employee'] ?>"><br>

        <label for="location">Location:</label>
        <input type="text" id="location" name="location" value="<?= $row['location'] ?>"><br>

        <label for="room">Room:</label>
        <input type="text" id="room" name="room" value="<?= $row['room'] ?>"><br>

        <label for="name">Ticket Title:</label>
        <input type="text" id="name" name="name" value="<?= $row['name'] ?>"><br>

        <label for="description">Ticket Description:</label>
        <textarea id="description" name="description"><?= $row['description'] ?></textarea><br>

        <label for="due_date">Ticket Due:</label>
        <input type="date" id="due_date" name="due_date" value="<?= $row['due_date'] ?>"><br>

        <label for="status">Current Status:</label>
        <select id="status" name="status">
            <option value="Open" <?= ($row['status'] == 'open') ? ' selected' : '' ?>>Open</option>
            <option value="Closed" <?= ($row['status'] == 'closed') ? ' selected' : '' ?>>Closed</option>
            <option value="Resolved" <?= ($row['status'] == 'resolved') ? ' selected' : '' ?>>Resolved</option>
            <option value="Pending" <?= ($row['status'] == 'pending') ? ' selected' : '' ?>>Pending</option>
            <option value="Vendor" <?= ($row['status'] == 'vendor') ? ' selected' : '' ?>>Vendor</option>
            <option value="Maintenance" <?= ($row['status'] == 'maintenance') ? ' selected' : '' ?>>Maintenance</option>
        </select><br>

        <h2>Notes</h2>
        <!-- Loop through the notes and display them -->
        <?php foreach (json_decode($row['notes'], true) as $note) : ?>
            <div class="note">
                <p>Note: <?= $note['note'] ?></p>
                <p>Created By: <?= $note['creator'] ?></p>
                <p>Created At: <?= $note['created'] ?></p>
                <p>Time: <?= $note['time'] ?></p>
            </div>
        <?php endforeach; ?>

        <!-- Add a submit button to update the information -->
        <input type="submit" value="Update Ticket">
    </form>
</article>








<?php include("../../includes/footer.php"); ?>