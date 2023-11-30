<?php
    require_once("block_file.php");
    require_once(from_root("/includes/tickets_template.php"));

    $ticket_query = "SELECT *
        FROM tickets
        WHERE status NOT IN ('Closed', 'Resolved')
        AND client = '" . $_SESSION['username'] . "'
        ORDER BY id ASC";

        $ticket_result = mysqli_query($database, $ticket_query);
        $tickets = mysqli_fetch_all($ticket_result, MYSQLI_ASSOC);
?>

<h1>My Tickets</h1>

<?php display_tickets_table($tickets, $database); ?>