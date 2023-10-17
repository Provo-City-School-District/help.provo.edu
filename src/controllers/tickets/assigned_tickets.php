<h1>My Assigned Tickets</h1>
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
        while ($ticket_row = mysqli_fetch_assoc($ticket_result)) {

        ?>
            <tr>
                <td data-cell="ID"><a href="/controllers/tickets/edit_ticket.php?id=<?= $ticket_row["id"]; ?>"><?= $ticket_row["id"] ?></a></td>
                <td data-cell="Subject"><a href="/controllers/tickets/edit_ticket.php?id=<?= $ticket_row["id"]; ?>"><?= $ticket_row["name"] ?></a></td>
                <td data-cell="Request Detail"><?= limitChars(html_entity_decode($ticket_row["description"]), 100) ?></td>
                <td data-cell="Location">
                    <?php
                    // Query the sites table to get the location name
                    $location_query = "SELECT location_name FROM locations WHERE sitenumber = " . $ticket_row["location"];
                    $location_result = mysqli_query($database, $location_query);
                    $location_name = mysqli_fetch_assoc($location_result)['location_name'];

                    // Display the location name and room number
                    echo $location_name . '<br><br>RM ' . $ticket_row['room'];
                    ?>
                </td>
                <td data-cell="Category">
                    <?php
                    if ($ticket_row['request_type_id'] === '0') {
                        echo "Other";
                    } else {
                        $request_type_query = "SELECT request_name FROM request_type WHERE request_id = " . $ticket_row['request_type_id'];
                        $request_type_query_result = mysqli_query($database, $request_type_query);
                        $request_type_name = mysqli_fetch_assoc($request_type_query_result)['request_name'];
                        echo $request_type_name;
                    }
                    ?>
                </td>
                <td data-cell="Assigned Employee"><?= $ticket_row['employee'] ?></td>
                <td data-cell="Current Status"><?= $ticket_row['status'] ?></td>
                <td data-cell="Created"><?= $ticket_row['created'] ?></td>
                <td data-cell="Last Updated"><?= $ticket_row['last_updated'] ?></td>
                <?php
                // Get the priority value from the ticket row
                $priority = $ticket_row['priority'];
                // Calculate the due date by adding the priority days to the created date
                $created_date = new DateTime($ticket_row['created']);
                $due_date = clone $created_date;
                $due_date->modify("+{$priority} weekdays");

                // Check if the due date falls on a weekend or excluded date
                while (isWeekend($due_date)) {
                    $due_date->modify("+1 day");
                }
                $count = hasExcludedDate($created_date->format('Y-m-d'), $due_date->format('Y-m-d'));
                if ($count > 0) {
                    $due_date->modify("{$count} day");
                }
                // Format the due date as a string
                $due_date = $due_date->format('Y-m-d');
                ?>
                <td data-cell="Due"><?= $due_date ?></td>
            </tr>
        <?php
        } // end while
        ?>
    </tbody>
</table>
