<?php
require_once("block_file.php");
include("header.php");
require_once('helpdbconnect.php');
require_once('swdbconnect.php');
include("ticket_utils.php");
?>
<h1>Search Ticket Information</h1>
<?php
if (user_is_tech($_SESSION['username'])) {
    //Technician Search Form
?>
    <div class="tabNav">
        <button class="tablinks" onclick="openTab(event, 'Tickets')">Search Tickets</button>
        <button class="tablinks" onclick="openTab(event, 'Notes')">Search Notes</button>
    </div>

    <div id="Tickets" class="tabcontent">
        <h2>Search Tickets</h2>
        <?php include __DIR__ . '/../../includes/templates/search_tickets_tech_form.php'; ?>
    </div>

    <div id="Notes" class="tabcontent">
        <h2>Search Notes</h2>
        <?php include __DIR__ . '/../../includes/templates/search_notes_form.php'; ?>
    </div>
<?php
    //End Technician Search Forms
} else {
    //Non-Technician Search Form
    include __DIR__ . '/../../includes/templates/search_tickets_client_form.php';
}
// Display Results
?>
<div id="results">

</div>

<script src="/includes/js/pages/search_tickets.js?v=1.0.0" type="text/javascript"></script>
<script>
    handleSubmit("searchClient", "includes/search_tickets_client_query_builder.php");
    handleSubmit("searchTickets", "includes/search_tickets_tech_query_builder.php");
    handleSubmit("searchNotes", "includes/search_notes_query_builder.php");
</script>
<?php include("footer.php"); ?>