<?php
require_once("block_file.php");

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
    $updated_time = trim(htmlspecialchars($_POST['note_time']));

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

    if (intval($updated_time) <= 0) {
        $error = "Note time must be greater than 0";
        $_SESSION['current_status'] = $error;
        $_SESSION['status_type'] = "error";
        $formData = http_build_query($_POST);
        header("Location: edit_ticket.php?id=$ticket_id&$formData");
        exit;
    }
    $timestamp = date('Y-m-d H:i:s');

    // Get visible to client state
    $visible_to_client = 0;
    if (isset($_POST["visible_to_client"])) {
        $visible_to_client = 1;
    }

    // Update the note in the database
    $query = "UPDATE notes SET note = ?, time = ?, visible_to_client = ?, date_override = ? WHERE note_id = ?";
    $stmt = mysqli_prepare($database, $query);
    mysqli_stmt_bind_param($stmt, "ssisi", $updated_note, $updated_time, $visible_to_client, $updated_date_override, $note_id);
    mysqli_stmt_execute($stmt);

    // Log the note update in the ticket_logs table
    $noteColumn = "note";
    $log_query = "INSERT INTO ticket_logs (ticket_id, user_id, field_name, old_value, new_value, created_at) VALUES (?, ?, ?, ?, ?, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'))";
    $log_stmt = mysqli_prepare($database, $log_query);
    mysqli_stmt_bind_param($log_stmt, "issss", $ticket_id, $_SESSION['username'],  $noteColumn, $note['note'], $updated_note);
    mysqli_stmt_execute($log_stmt);

    // Redirect back to the edit ticket page
    $_SESSION['current_status'] = "Note edited successfully";
    $_SESSION['status_type'] = "success";
    header("Location: edit_ticket.php?id=$ticket_id");
    exit();
}
?>
<?php include("header.php"); ?>
<h2>Edit Note</h2>
<form method="post">
    <input type="hidden" name="note_id" value="<?= $note_id ?>">
    <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
    <label for="note">Note:</label>
    <textarea id="note" name="note" class="tinyMCEtextarea"><?= $note['note'] ?></textarea><br>

    <label for="note_time">Time in Minutes:</label>
    <input id="note_time" name="note_time" value="<?= $note['time'] ?>"><br>
    <!-- TODO: Hide the visible to client option for non admins,
                forms make this a pain because it needs to submit a value if false, system currentlyelies on not receiving
                a value to assume no (thus hiding it from client) 
            
                Although non admins maybe shouldn't be able to edit notes anyway?
            -->
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
            echo "style=\"display:none;\""; ?> id="date_override_input" type="datetime-local" name="date_override" value="<?= $note['date_override']?>"><br>
    <input type="submit" value="Save Note">
</form>
<script src="/includes/js/jquery-3.7.1.min.js"></script>
<script>
    $('input[name=date_override_enable]').on('change', function() {
        if (!this.checked) {
            $('#date_override_input').hide();
        } else {
            $('#date_override_input').show();
        }
    });
</script>
<?php include("footer.php"); ?>