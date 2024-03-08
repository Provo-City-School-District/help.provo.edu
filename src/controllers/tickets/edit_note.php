<?php
require_once("block_file.php");
require("ticket_utils.php");
require_once('init.php');
require_once('helpdbconnect.php');

// Get the note ID and ticket ID from the query string
$note_id = trim(htmlspecialchars($_GET['note_id']));
$ticket_id = trim(htmlspecialchars($_GET['ticket_id']));

// Fetch the note from the database
$query = "SELECT * FROM notes WHERE note_id = ?";
$stmt = mysqli_prepare($database, $query);
mysqli_stmt_bind_param($stmt, "i", $note_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$note = mysqli_fetch_assoc($result);

//set fields to zero if time was initially set prior to time tracking change
if ($note['work_hours'] === null && $note['work_minutes'] === null && $note['travel_hours'] === null && $note['travel_minutes'] === null) {
    $note['work_hours'] = 0;
    $note['work_minutes'] = 0;
    $note['travel_hours'] = 0;
    $note['travel_minutes'] = 0;
}

// Check if the note belongs to the current user
if ($note['creator'] !== $_SESSION['username']) {
    // Redirect to the edit ticket page if the note does not belong to the current user

    //need to make a message that "cant edit others' notes"
    header("Location: edit_ticket.php?id=$ticket_id");
    exit();
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the updated note and time from the form data
    $updated_note = trim(htmlspecialchars($_POST['note']));
    $work_hours = trim(htmlspecialchars($_POST['work_hours']));
    $work_minutes = trim(htmlspecialchars($_POST['work_minutes']));
    $travel_hours = trim(htmlspecialchars($_POST['travel_hours']));
    $travel_minutes = trim(htmlspecialchars($_POST['travel_minutes']));




    $updated_date_override = null;
    if (isset($_POST["date_override_enable"])) {

        // validate it can be created into a date
        $date_override_timestamp = strtotime($_POST["date_override"]);
        $updated_date_override = date('Y-m-d H:i:s', $date_override_timestamp);
        if (!$updated_date_override || !$date_override_timestamp) {
            $error = "Date override was invalid";
            $_SESSION['current_status'] = $error;
            $_SESSION['status_type'] = "error";
            $formData = http_build_query($_POST);
            header("Location: edit_ticket.php?id=$ticket_id&$formData");
            exit;
        }
    }
    if (!isset($work_hours) || $work_hours === null || !isset($work_minutes) || $work_minutes === null || !isset($travel_hours) || $travel_hours === null || !isset($travel_minutes) || $travel_minutes === null) {
        $error = "Note time must be greater than 0";
        $_SESSION['current_status'] = $error;
        $_SESSION['status_type'] = "error";
        $formData = http_build_query($_POST);

        exit;
    }

    $timestamp = date('Y-m-d H:i:s');

    // Get visible to client state
    $visible_to_client = 0;
    if (isset($_POST["visible_to_client"])) {
        $visible_to_client = 1;
    }

    // Update the note in the database
    $query = "UPDATE notes SET note = ?, work_hours = ?, work_minutes = ?, travel_hours = ?, travel_minutes = ?, visible_to_client = ?, date_override = ? WHERE note_id = ?";
    $stmt = mysqli_prepare($database, $query);
    mysqli_stmt_bind_param($stmt, "siiiiisi", $updated_note, $work_hours, $work_minutes, $travel_hours, $travel_minutes, $visible_to_client, $updated_date_override, $note_id);
    mysqli_stmt_execute($stmt);

    // Log the note update in the ticket_logs table
    $noteColumn = "note";
    logTicketChange($database, $ticket_id, $_SESSION['username'], $noteColumn, $note['note'], $updated_note);

    // Redirect back to the edit ticket page
    $_SESSION['current_status'] = "Note edited successfully";
    $_SESSION['status_type'] = "success";
    header("Location: edit_ticket.php?id=$ticket_id");
    exit();
}
?>
<?php include("header.php"); ?>
<h2>Edit Note</h2>
<form method="post" id="note-submit">
    <input type="hidden" name="note_id" value="<?= $note_id ?>">
    <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
    <label for="note">Note:</label>
    <textarea id="note" name="note" class="tinyMCEtextarea"><?= $note['note'] ?></textarea><br>

    <?php if (session_is_tech()) : ?>
        <h4>Work Time</h4>
        <div>
            <label for="work_hours">Hours:</label>
            <input id="work_hours" name="work_hours" type="number" value="<?= $note['work_hours'] ?>" required>

            <label for="work_minutes">Minutes:</label>
            <input id="work_minutes" name="work_minutes" type="number" value="<?= $note['work_minutes'] ?>" required>
        </div>
        <h4>Travel Time</h4>
        <div>
            <label for="travel_hours">Hours:</label>
            <input id="travel_hours" name="travel_hours" type="number" value="<?= $note['travel_hours'] ?>" required>

            <label for="travel_minutes">Minutes:</label>
            <input id="travel_minutes" name="travel_minutes" type="number" value="<?= $note['travel_minutes'] ?>" required>
        </div>

        <div>
            <label for="total_time">Total Time in Minutes:</label>
            <input id="total_time" name="total_time" type="number" readonly>
        </div>
        <label for="visible_to_client">Visible to Client:</label>
        <input type="checkbox" id="visible_to_client" name="visible_to_client" <?php
                                                                                if ($note['visible_to_client'] == 1) {
                                                                                    echo "checked=\"checked\"";
                                                                                }
                                                                                ?> value="true"><br>
        <label for="date_override_enable">Date Override:</label>
        <input <?php
                if ($note['date_override'] != null)
                    echo "checked=\"checked\""; ?> type="checkbox" id="date_override_enable" name="date_override_enable">
        <input <?php
                if ($note['date_override'] == null)
                    echo "style=\"display:none;\""; ?> id="date_override_input" type="datetime-local" name="date_override" value="<?= $note['date_override'] ?>"><br>
    <?php else : ?>
        <input type="hidden" id="visible_to_client" name="visible_to_client" value="1">
        <input id="total_time" name="total_time" type="hidden" value="0">
        <input id="travel_minutes" name="travel_minutes" type="hidden" value="0" required>
        <input id="travel_hours" name="travel_hours" type="hidden" value="0" required>
        <input id="work_minutes" name="work_minutes" type="hidden" value="0" required>
        <input id="work_hours" name="work_hours" type="hidden" value="0" required>
    <?php endif; ?>
    <!-- TODO: Hide the visible to client option for non admins,
                forms make this a pain because it needs to submit a value if false, system currentlyelies on not receiving
                a value to assume no (thus hiding it from client) 
            
                Although non admins maybe shouldn't be able to edit notes anyway?
            -->
    <input type="submit" value="Save Note" class="button">
    <button onclick="window.location.href='edit_ticket.php?id=<?= $ticket_id ?>'" class="button">Cancel</button>

</form>
<form method="post" action="delete_note.php" id="delete-note-form">
    <input type="hidden" name="note_id" value="<?php echo $note_id; ?>">
    <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
    <input type="submit" value="Delete Note" id="delete-note" class="button">
</form>
<script src="/includes/js/jquery-3.7.1.min.js"></script>
<?php if (session_is_tech()) : ?>
    <script>
        $('input[name=date_override_enable]').on('change', function() {
            if (!this.checked) {
                $('#date_override_input').hide();
            } else {
                $('#date_override_input').show();
            }
        });

        document.querySelectorAll('#work_hours, #work_minutes, #travel_hours, #travel_minutes').forEach(function(el) {
            el.addEventListener('input', function() {
                var workHours = parseInt(document.getElementById('work_hours').value) || 0;
                var workMinutes = parseInt(document.getElementById('work_minutes').value) || 0;
                var travelHours = parseInt(document.getElementById('travel_hours').value) || 0;
                var travelMinutes = parseInt(document.getElementById('travel_minutes').value) || 0;

                var totalTime = (workHours + travelHours) * 60 + workMinutes + travelMinutes;

                document.getElementById('total_time').value = totalTime;
            });
        });

        // add alert if no time is entered
        document.getElementById('note-submit').addEventListener('submit', function(e) {
            var fields = ['work_hours', 'work_minutes', 'travel_hours', 'travel_minutes'];
            var allZero = fields.every(function(field) {
                return parseInt(document.getElementById(field).value, 10) === 0;
            });

            if (allZero) {
                alert('Please enter a value greater than 0 for at least one of the time fields.');
                e.preventDefault(); // Prevent the form submission
            }
        });
    </script>
    <script>
        // Add a click event listener to the delete button
        $('#delete-note').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this note?')) {
                e.preventDefault(); // Prevent the navigation
            }
        });
    </script>
<?php endif; ?>
<?php include("footer.php"); ?>