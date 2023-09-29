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
$ticket_id = $_GET['id'];
$query_ticket_id = substr($ticket_id, 2);

$old_ticket_query = "SELECT JOB_TICKET_ID,PROBLEM_TYPE_ID,SUBJECT,QUESTION_TEXT,REPORT_DATE,LAST_UPDATED,JOB_TIME,ASSIGNED_TECH_ID,ROOM,LOCATION_ID,DEPARTMENT_ID,CLOSE_DATE,CLIENT_ID FROM whd.job_ticket WHERE JOB_TICKET_ID = $query_ticket_id";
$old_ticket_result = mysqli_query($swdb, $old_ticket_query);
?>
<article id="ticketWrapper">
    <?php
     print_r(mysqli_fetch_assoc($old_ticket_result));
    ?>
</article>
<?php
include("../../includes/footer.php");
?>