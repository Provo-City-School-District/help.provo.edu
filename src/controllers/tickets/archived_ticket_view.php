<?php
include("../../includes/header.php");
require_once('../../includes/swdbconnect.php');
// Check if the user is logged in
if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['can_view_tickets'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to view tickets.';
        exit;
    }
}

// TODO could cache these
function get_client_name_from_id(string $client_sw_id)
{
    global $swdb;

    $client_name_query = "SELECT FIRST_NAME, LAST_NAME FROM client WHERE CLIENT_ID = '$client_sw_id'";
    $client_name_result = mysqli_query($swdb, $client_name_query);
    $client_name_data = mysqli_fetch_assoc($client_name_result);
    $client_name = trim($client_name_data["FIRST_NAME"])." ".trim($client_name_data["LAST_NAME"]);

    return $client_name;
}

// TODO could cache these
function get_tech_name_from_id(string $tech_sw_id)
{
    global $swdb;

    $tech_name_query = "SELECT FIRST_NAME, LAST_NAME FROM tech WHERE CLIENT_ID = '$tech_sw_id'";
    $tech_name_result = mysqli_query($swdb, $tech_name_query);
    $tech_name_data = mysqli_fetch_assoc($tech_name_result);
    $tech_name = trim($tech_name_data["FIRST_NAME"])." ".trim($tech_name_data["LAST_NAME"]);

    return $tech_name;
}

// TODO could cache these
function get_location_name_from_id(string $location_sw_id)
{
    global $swdb;
    $location_name_query = "SELECT LOCATION_NAME FROM location WHERE LOCATION_ID = '$location_sw_id'";
    $location_name_result = mysqli_query($swdb, $location_name_query);
    $location_name_data = mysqli_fetch_assoc($location_name_result);
    $location_name = trim($location_name_data["LOCATION_NAME"]);

    return $location_name;
}

$ticket_id = $_GET['id'];
$query_ticket_id = substr($ticket_id, 2);

$old_ticket_query = "SELECT JOB_TICKET_ID,PROBLEM_TYPE_ID,SUBJECT,QUESTION_TEXT,REPORT_DATE,LAST_UPDATED,JOB_TIME,ASSIGNED_TECH_ID,ROOM,LOCATION_ID,DEPARTMENT_ID,CLOSE_DATE,CLIENT_ID,CLIENT_CREATOR_ID FROM whd.job_ticket WHERE JOB_TICKET_ID = $query_ticket_id";
$old_ticket_result = mysqli_query($swdb, $old_ticket_query);
$arch_ticket_data = mysqli_fetch_assoc($old_ticket_result);



$tech_sw_id = $arch_ticket_data['ASSIGNED_TECH_ID'];
$tech_name = get_tech_name_from_id($tech_sw_id);

$client_sw_id = $arch_ticket_data['CLIENT_ID'];
$client_name = get_client_name_from_id($client_sw_id);

$location_sw_id = $arch_ticket_data['LOCATION_ID'];
$location_name = get_location_name_from_id($location_sw_id);
/*
$creator_id = $arch_ticket_data["CLIENT_CREATOR_ID"];
$creator_name_query = "SELECT FIRST_NAME, LAST_NAME FROM client WHERE CLIENT_ID = '$creator_id'";
$creator_name_result = mysqli_query($swdb, $creator_name_query);
$creator_name_data = mysqli_fetch_assoc($creator_name_result);
$creator_name = trim($creator_name_data["FIRST_NAME"])." ".trim($creator_name_data["LAST_NAME"]);
*/
$all_notes = [];

$tech_notes_query = "SELECT TECHNICIAN_ID, NOTE_TEXT, CREATION_DATE, HIDDEN, TECH_NOTE_DATE, BILLING_MINUTES FROM TECH_NOTE WHERE JOB_TICKET_ID = '$query_ticket_id'";
$tech_notes_result = mysqli_query($swdb, $tech_notes_query);

while ($tech_note_row = mysqli_fetch_assoc($tech_notes_result)) {
    $note_text = $tech_note_row["NOTE_TEXT"];
    $tech_id = $tech_note_row["TECHNICIAN_ID"];
    $created_date = $tech_note_row["CREATION_DATE"];
    $hidden = $tech_note_row["HIDDEN"];
    $effective_date = $tech_note_row["TECH_NOTE_DATE"];
    $note_time = $tech_note_row["BILLING_MINUTES"];
    
    if ($note_time == null)
        $note_time = 0;

    $note_date = $effective_date;
    if ($created_date != $effective_date) {
        $note_date = $effective_date."*";
    }
    $all_notes[] = [
        "creator" => get_tech_name_from_id($tech_id), 
        "text" => $note_text,
        "date" => $note_date,
        "time" => $note_time,
        "hidden" => $hidden
    ];
}

$client_notes_query = "SELECT CLIENT_ID, TICKET_DATE, NOTE_TEXT FROM CLIENT_NOTE WHERE JOB_TICKET_ID = '$query_ticket_id'";
$client_notes_result = mysqli_query($swdb, $client_notes_query);

while ($client_note_row = mysqli_fetch_assoc($client_notes_result)) {
    $client_id = $client_note_row["CLIENT_ID"];
    $note_text = $client_note_row["NOTE_TEXT"];
    $note_date = $client_note_row["TICKET_DATE"];

    $all_notes[] = [
        "creator" => get_client_name_from_id($client_id), 
        "text" => $note_text,
        "date" => $note_date,
        "time" => "—",
        "hidden" => false
    ];
}

// Sort tickets by date
usort($all_notes, function ($item1, $item2) {
    // filter out * which could be added by date override
    $item1_str_clean = str_replace("*", "", $item1["date"]);
    $item2_str_clean = str_replace("*", "", $item2["date"]);
    return strtotime($item1_str_clean) <=> strtotime($item2_str_clean);
});

?>
<article id="ticketWrapper">
    <h1>Archived Ticket# <?= $arch_ticket_data['JOB_TICKET_ID'] ?></h1>
    created: <?= $arch_ticket_data['REPORT_DATE'] ?><br>
    last updated: <?= $arch_ticket_data['LAST_UPDATED'] ?><br>
    closed: <?= $arch_ticket_data['CLOSE_DATE'] ?><br>

    <h2>Ticket Information</h2>
    <div class="ticketGrid">
        <div>
            Client: <?= $client_name ?>
        </div>
        <div>
            Tech: <?= $tech_name ?>
        </div>
        <div>
            Location: <?= $location_name ?>
        </div>
        <div>
            Room: <?= $arch_ticket_data['ROOM'] ?>
        </div>
        <div>
            Department: <?= $arch_ticket_data['DEPARTMENT_ID'] ?>   
        </div>
        <div>
            Problem Type: <?= $arch_ticket_data['PROBLEM_TYPE_ID'] ?>   
        </div>
    </div>
    <div class="detailContainer">
        <div class="grid2 ticketSubject">
            <span>Ticket Title:</span> <?= $arch_ticket_data['SUBJECT'] ?>
        </div>
        <div>
            Question: <?= $arch_ticket_data['QUESTION_TEXT'] ?>
        </div>
</article>

<h2>Notes</h2>
<div class="note">
    <table class="ticketsTable">
        <tr>
            <th>Date</th>
            <th>Created By</th>
            <th>Note</th>
            <th>Time</th>
        </tr>
    <?php
    foreach ($all_notes as $note):
        $note_creator = $note["creator"];
        $note_text = $note["text"];
        $note_date = $note["date"];
        $note_time = $note["time"];
        $note_hidden = $note["hidden"];
    ?>
        <tr>
            <td data-cell="Date"><?= $note_date ?></td>
            <td data-cell="Created By"><?= $note_creator ?></td>
            <td data-cell="Note Message"><?= $note_text ?>
            <span class="note_id">
                <?= !$note_hidden ? "Visible to Client" : "Invisible to Client"; ?>
            </span>
            </td>
            <td data-cell="Time Taken"><?= $note_time ?></td>
        </tr>
    <?php endforeach; ?>
    <tr class="totalTime">
        <td data-cell="Total Time" colspan=4><span>Total Time: </span> <?= $arch_ticket_data['JOB_TIME'] ?></td>
    </tr>
    </table>
</div>
<?php
include("../../includes/footer.php");
?>