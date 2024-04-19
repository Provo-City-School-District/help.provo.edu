<?php
require_once("block_file.php");
require_once(from_root("/includes/tickets_template.php"));
include("header.php");
require_once('helpdbconnect.php');
include("ticket_utils.php");

//page query
$ticket_query = "(SELECT * FROM tickets WHERE client = ?)
        UNION
        (SELECT tickets.* FROM tickets 
        JOIN notes ON tickets.id = notes.linked_id 
        WHERE notes.creator = ?)
        ORDER BY last_updated DESC";

$username = $_SESSION["username"];
$ticket_result = $database->execute_query($ticket_query, [$username, $username]);

?>
<h1>Ticket History</h1>

<?php display_tickets_table($ticket_result, $database); ?>
<?php include("footer.php"); ?>