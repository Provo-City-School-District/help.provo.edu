<?php
include("includes/header.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    if ($_SESSION['permissions']['can_view_tickets'] == 0) {
        // User does not have permission to view tickets
        echo 'You do not have permission to view tickets.';
        exit;
    }
}
require_once('includes/helpdbconnect.php');
?>

<h1> Tickets Page</h1>
<table class="ticketsTable data-table">
    <thead>
        <tr>
            <th class="tID">ID</th>
            <th>Subject</th>
            <th>Request Detail</th>
            <th class="tLocation">Location</th>
            <th>Request Category</th>
            <th class="tUser">Assigned Tech</th>
            <th>Current Status</th>
            <th class="tDate">Created</th>
            <th class="tDate">Last Updated</th>
            <th class="tDate">Due</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Execute the SQL query
        $ticket_query = "SELECT *
        FROM tickets
        WHERE status NOT IN ('Closed', 'Resolved')
        AND employee = '" . $_SESSION['username'] . "'
        ORDER BY id ASC";

        $ticket_result = mysqli_query($database, $ticket_query);
        while ($row = mysqli_fetch_assoc($ticket_result)) {
            $last_update = date("y-m-d", strtotime($row['last_updated']));
            $created = $row['created']; // Get the value from the database

            if ($created !== null) {
                $created = date("y-m-d", strtotime($created)); // Convert to date if not null
            } else {
                $created = ''; // Set to an empty string or handle the case as needed
            }
            $due_date = date("y-m-d", strtotime($row['due_date']));
            $overdue = strtotime($due_date) < strtotime(date("Y-m-d"));
        ?>
            <tr>
                <td data-cell="ID"><a href="/controllers/tickets/edit_ticket.php?id=<?= $row["id"]; ?>"><?= $row["id"] ?></a></td>
                <td data-cell="Subject"><a href="/controllers/tickets/edit_ticket.php?id=<?= $row["id"]; ?>"><?= $row["name"] ?></a></td>
                <td data-cell="Request Detail"><?= html_entity_decode($row["description"]) ?></td>
                <td data-cell="Location"><?= $row["location"] ?> <br><br>RM <?= $row['room'] ?></td>
                <td data-cell="Category"></td>
                <td data-cell="Assigned Employee"><?= $row['employee'] ?></td>
                <td data-cell="Current Status"><?= $row['status'] ?></td>
                <td data-cell="Created"><?= $created ?></td>
                <td data-cell="Last Updated"><?= $last_update ?></td>
                <?php if ($overdue) { ?>
                    <td data-cell="Due"><p class="warning"><?= $due_date ?></p>
                    </td>
                <?php } else { ?>
                    <td data-cell="Due"><?= $due_date ?></td>
                <?php } ?>
            </tr>
        <?php
        } // end while
        ?>
    </tbody>
</table>

<?php include("includes/footer.php"); ?>