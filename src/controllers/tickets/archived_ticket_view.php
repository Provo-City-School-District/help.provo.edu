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
$arch_ticket_data = mysqli_fetch_assoc($old_ticket_result);
?>
<article id="ticketWrapper">
    <h1>Archived Ticket# <?= $arch_ticket_data['JOB_TICKET_ID'] ?></h1>
    created: <?= $arch_ticket_data['REPORT_DATE'] ?><br>
    last updated: <?= $arch_ticket_data['LAST_UPDATED'] ?><br>
    closed: <?= $arch_ticket_data['CLOSE_DATE'] ?><br>

    <h2>Ticket Information</h2>
    <div class="ticketGrid">
        <div>
            Client: <?= $arch_ticket_data['CLIENT_ID'] ?>
        </div>
        <div>
            Employee: <?= $arch_ticket_data['ASSIGNED_TECH_ID'] ?>
        </div>
        <div>
            Location: <?= $arch_ticket_data['LOCATION_ID'] ?>
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
        <div>
            Job Time: <?= $arch_ticket_data['JOB_TIME'] ?>
        </div>
</article>
<?php
include("../../includes/footer.php");
?>