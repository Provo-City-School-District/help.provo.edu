<?php
include("../../includes/header.php");
include("../../vendor/autoload.php");
// Check if the user is logged in
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
        'note_id', notes.note_id,
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
        <input type="submit" value="Update Ticket">
        <div class="ticketGrid">
            <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
            <input type="hidden" name="madeby" value="<?= $_SESSION['username'] ?>">
            <div>
                <label for="client">Client:</label>
                <input type="text" id="client" name="client" value="<?= $row['client'] ?>">
            </div>

            <div> <label for="employee">Assigned Tech:</label>
                <select id="employee" name="employee">
                    <?php foreach ($usernames as $username) : ?>
                        <option value="<?= $username ?>" <?= $row['employee'] === $username ? 'selected' : '' ?>><?= $username ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="location">Location:</label>
                <input type="text" id="location" name="location" value="<?= $row['location'] ?>">
            </div>

            <div>
                <label for="room">Room:</label>
                <input type="text" id="room" name="room" value="<?= $row['room'] ?>">
            </div>

            <div>
            <label for="name">Ticket Title:</label>
            <input type="text" id="name" name="name" value="<?= $row['name'] ?>">
            </div>


            <div>
            <label for="due_date">Ticket Due:</label>
            <input type="date" id="due_date" name="due_date" value="<?= $row['due_date'] ?>">
            </div>

            <div>
            <label for="status">Current Status:</label>
            <select id="status" name="status">
                <option value="open" <?= ($row['status'] == 'open') ? ' selected' : '' ?>>Open</option>
                <option value="closed" <?= ($row['status'] == 'closed') ? ' selected' : '' ?>>Closed</option>
                <option value="resolved" <?= ($row['status'] == 'resolved') ? ' selected' : '' ?>>Resolved</option>
                <option value="pending" <?= ($row['status'] == 'pending') ? ' selected' : '' ?>>Pending</option>
                <option value="vendor" <?= ($row['status'] == 'vendor') ? ' selected' : '' ?>>Vendor</option>
                <option value="maintenance" <?= ($row['status'] == 'maintenance') ? ' selected' : '' ?>>Maintenance</option>
            </select>
            </div>
        </div>
        <div class="detailContainer">
            <label for="description">Request Detail:</label>
            <div class="ticket-description">
                <?= html_entity_decode($row['description']) ?>
                <button id="edit-description-button" type="button">Edit Request Detail</button>
            </div>

            <div id="edit-description-form" style="display: none;">
                <textarea id="description" name="description" class="tinyMCEtextarea"><?= $row['description'] ?></textarea>
            </div>
        </div>

    </form>
    <!-- Loop through the notes and display them -->
    <?php if ($row['notes'] !== null) : ?>
        <h2>Notes</h2>
        <div class="note">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Creator</th>
                    <th>Note</th>
                    <th>Time</th>
                </tr>
                <?php
                $total_time = 0; // Initialize total time to 0
                foreach (json_decode($row['notes'], true) as $note) :
                    $total_time += $note['time']; // Add note time to total time
                ?>

                    <tr>
                        <td><a href="edit_note.php?note_id=<?= $note['note_id'] ?>&ticket_id=<?= $ticket_id ?>"><?= $note['created'] ?></a></td>
                        <td><?= $note['creator'] ?></td>
                        <td><?= html_entity_decode($note['note']) ?><span class="note_id"><a href="edit_note.php?note_id=<?= $note['note_id'] ?>&ticket_id=<?= $ticket_id ?>">Note#: <?= html_entity_decode($note['note_id']) ?></a></span></td>
                        <td><?= $note['time'] ?></td>

                    </tr>

                <?php endforeach; ?>
            <?php endif; ?>
            <tr class="totalTime">
                <td>Total Time</td>
                <td><?= $total_time ?></td>
            </tr>
            </table>

        </div>
        <button id="new-note-button">New Note</button>
        <div id="new-note-form" style="display: none;">
            <h3>Add Note</h3>
            <form method="post" action="add_note_handler.php">
                <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
                <input type="hidden" name="username" value="<?= $_SESSION['username'] ?>">
                <label for="note">Note:</label>
                <textarea id="note" name="note" class="tinyMCEtextarea"></textarea>

                <label for="note_time">Time in Minutes:</label>
                <input id="note_time" name="note_time" type="number">
                <input type="submit" value="Add Note">
            </form>
        </div>
        <?php
        // Fetch the ticket logs for the current ticket
        $log_query = "SELECT field_name,user_id, old_value, new_value, created_at FROM ticket_logs WHERE ticket_id = ? ORDER BY created_at DESC";
        $log_stmt = mysqli_prepare($database, $log_query);
        mysqli_stmt_bind_param($log_stmt, "i", $ticket_id);
        mysqli_stmt_execute($log_stmt);
        $log_result = mysqli_stmt_get_result($log_stmt);

        // Display the ticket logs in a table
        if (mysqli_num_rows($log_result) > 0) {
        ?>
            <div class="ticket_log">
                <h2>Ticket History</h2>
                <table>
                    <tr>
                        <th>Changed By</th>
                        <th>Created At</th>
                        <th>Changes made</th>
                    </tr>
                    <?php
                    while ($log_row = mysqli_fetch_assoc($log_result)) {
                    ?>
                        <tr>
                            <td><?= $log_row['user_id'] ?></td>
                            <td><?= $log_row['created_at'] ?></td>
                            <td>
                                <?php
                                if ($log_row['field_name'] != 'note') {
                                    echo $log_row['field_name'] . ' from: ' . html_entity_decode($log_row['old_value']) . ' to: ' . html_entity_decode($log_row['new_value']);
                                } else {
                                    if ($log_row['old_value'] != null) {
                                        echo 'Note Updated: ' . html_entity_decode($log_row['old_value']) . ' to: ' . html_entity_decode($log_row['new_value']);
                                    } else {
                                        echo 'Note Created: ' . html_entity_decode($log_row['new_value']);
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </table>
            </div>
        <?php
        }
        ?>
</article>



<?php include("../../includes/footer.php"); ?>