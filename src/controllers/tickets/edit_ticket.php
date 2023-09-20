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
tickets.id,
tickets.client,
tickets.employee,
tickets.location,
tickets.room,
tickets.name,
tickets.description,
tickets.created,
tickets.last_updated,
tickets.due_date,
tickets.status,
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

// Fetch the list of usernames from the users table
$usernamesQuery = "SELECT username FROM users";
$usernamesResult = mysqli_query($database, $usernamesQuery);

if (!$usernamesResult) {
    die('Error fetching usernames: ' . mysqli_error($database));
}

// Store the usernames in an array
$usernames = array();
while ($usernameRow = mysqli_fetch_assoc($usernamesResult)) {
    $usernames[] = $usernameRow['username'];
}

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
        <!-- Add a submit button to update the information -->
        <input type="submit" value="Update Ticket"><br>
        <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
        <label for="client">Client:</label>
        <input type="text" id="client" name="client" value="<?= $row['client'] ?>"><br>

        <label for="employee">Assigned Tech:</label>
        <select id="employee" name="employee">
            <?php foreach ($usernames as $username) : ?>
                <option value="<?= $username ?>" <?= $row['employee'] === $username ? 'selected' : '' ?>><?= $username ?></option>
            <?php endforeach; ?>
        </select><br>
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
            <option value="open" <?= ($row['status'] == 'open') ? ' selected' : '' ?>>Open</option>
            <option value="closed" <?= ($row['status'] == 'closed') ? ' selected' : '' ?>>Closed</option>
            <option value="resolved" <?= ($row['status'] == 'resolved') ? ' selected' : '' ?>>Resolved</option>
            <option value="pending" <?= ($row['status'] == 'pending') ? ' selected' : '' ?>>Pending</option>
            <option value="vendor" <?= ($row['status'] == 'vendor') ? ' selected' : '' ?>>Vendor</option>
            <option value="maintenance" <?= ($row['status'] == 'maintenance') ? ' selected' : '' ?>>Maintenance</option>
        </select><br>
    </form>
    <!-- Loop through the notes and display them -->
    <?php if ($row['notes'] !== null) : ?>
        <h2>Notes</h2>
        <?php foreach (json_decode($row['notes'], true) as $note) : ?>

            <div class="note">
                <p>Note: <?= $note['note'] ?></p>
                <p>Created By: <?= $note['creator'] ?></p>
                <p>Created At: <?= $note['created'] ?></p>
                <p>Time: <?= $note['time'] ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <h2>Add Note</h2>
    <form method="post" action="add_note_handler.php">
        <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
        <input type="hidden" name="username" value="<?= $_SESSION['username'] ?>">
        <label for="note">Note:</label>
        <textarea id="note" name="note"></textarea><br>

        <label for="note_time">Time in Minutes:</label>
        <input id="note_time" name="note_time"><br>
        <input type="submit" value="Add Note">
    </form>
</article>








<?php include("../../includes/footer.php"); ?>