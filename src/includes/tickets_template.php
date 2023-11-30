<?php
function display_tickets_table($tickets, $database)
{
    echo '<table class="ticketsTable data-table">
        <thead>
            <tr>
                <th class="tID">ID</th>
                <th>Request Detail</th>
                <th class="tLocation">Location</th>
                <th>Request Category</th>
                <th>Current Status</th>
                <th class="tDate">Last Updated</th>
                <th class="tDate">Due</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($tickets as $ticket) {
        // Query the sites table to get the location name
        $location_query = "SELECT location_name FROM locations WHERE sitenumber = " . $ticket["location"];
        $location_result = mysqli_query($database, $location_query);
        $location_name = mysqli_fetch_assoc($location_result)['location_name'];

        if ($ticket['request_type_id'] === '0') {
            $request_type_name = "Other";
        } else {
            $request_type_query = "SELECT request_name FROM request_type WHERE request_id = " . $ticket['request_type_id'];
            $request_type_query_result = mysqli_query($database, $request_type_query);
            $request_type_name = mysqli_fetch_assoc($request_type_query_result)['request_name'];
        }

        echo '<tr>
            <td data-cell="ID"><a href="/controllers/tickets/edit_ticket.php?id=' . $ticket["id"] . '">' . $ticket["id"] . '</a></td>
            <td data-cell="Request Detail"><a href="/controllers/tickets/edit_ticket.php?id=' . $ticket["id"] . '">' . $ticket["name"] . ':</a>' . limitChars(html_entity_decode($ticket["description"]), 100) . '</td>
            <td data-cell="Location">' . $location_name . '<br><br>RM ' . $ticket['room'] . '</td>
            <td data-cell="Request Category">' .  $request_type_name . '</td>
            <td data-cell="Current Status">' . $ticket["status"] . '</td>
            <td data-cell="Last Updated">' . $ticket["last_updated"] . '</td>
            <td data-cell="Due">' . $ticket["due_date"] . '</td>
        </tr>';
    }

    echo '</tbody></table>';
}
