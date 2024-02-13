<?php
function display_tickets_table($tickets, $database)
{
    echo '<table class="ticketsTable data-table">
        <thead>
            <tr>
                <th class="tID">ID</th>
                <th class="reqDetail">Request Detail</th>
                <th class="tLatestNote">Latest Note</th>
                <th class="client">Client</th>
                <th class="tLocation">Location</th>
                <th class="category">Request Category</th>
                <th class="status">Current Status</th>
                <th class="tDate">Last Updated</th>
                <th class="tDate">Due</th>
                <th class="">Assigned</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($tickets as $ticket) {
        // Query the sites table to get the location name
        $location_query = "SELECT location_name FROM locations WHERE sitenumber = ?";
        $loc_stmt = mysqli_prepare($database, $location_query);

        if ($loc_stmt) {
            mysqli_stmt_bind_param($loc_stmt, "s", $ticket["location"]);
            mysqli_stmt_execute($loc_stmt);
            mysqli_stmt_bind_result($loc_stmt, $location_name);

            // Fetch the result
            mysqli_stmt_fetch($loc_stmt);

            // Use $location_name as needed
            mysqli_stmt_close($loc_stmt);
        }

        if ($ticket['request_type_id'] === '0') {
            $request_type_name = "Other";
        } else {
            $request_type_query = "SELECT request_name FROM request_type WHERE request_id = " . $ticket['request_type_id'];
            $request_type_query_result = mysqli_query($database, $request_type_query);
            $request_type_name = mysqli_fetch_assoc($request_type_query_result)['request_name'];
        }

        $notes_query = "SELECT creator, note FROM help.notes WHERE linked_id = ? ORDER BY
            (CASE WHEN date_override IS NULL THEN created ELSE date_override END) DESC
        ";
        $notes_stmt = mysqli_prepare($database, $notes_query);
        $creator = null;
        $note_data = null;
        if ($notes_stmt) {
            mysqli_stmt_bind_param($notes_stmt, "i", $ticket["id"]);
            mysqli_stmt_execute($notes_stmt);

            mysqli_stmt_bind_result($notes_stmt, $creator, $note_data);
            // Fetch the result
            mysqli_stmt_fetch($notes_stmt);

            // Use $location_name as needed
            mysqli_stmt_close($notes_stmt);
        }
        $latest_note_str = "";
        if ($creator != null && $note_data != null)
            $latest_note_str = $creator . ": " . strip_tags(html_entity_decode($note_data));

        $descriptionWithoutLinks = strip_tags(html_entity_decode($ticket["description"]));
        if (isset($ticket["client"])) {
            $result = get_client_name($ticket["client"]);
            $clientFirstName = $result['firstname'];
            $clientLastName = $result['lastname'];
        }
        if (!isset($ticket['location']) || $ticket['location'] == null) {
            $location_name = "N/A";
        }
        echo '<tr>
            <td data-cell="ID"><a href="/controllers/tickets/edit_ticket.php?id=' . $ticket["id"] . '">' . $ticket["id"] . '</a></td>
            <td class="details" data-cell="Request Detail"><a href="/controllers/tickets/edit_ticket.php?id=' . $ticket["id"] . '">' . $ticket["name"] . ':</a>' . limitChars($descriptionWithoutLinks, 100) . '</td>
            <td data-cell="Latest Note:">' . limitChars($latest_note_str, 150) . '</td>
            <td data-cell="Client: ">' . $clientFirstName . " " . $clientLastName . " (" . $ticket['client'] . ")" . '</td>
            <td data-cell="Location">' . $location_name . '<br><br>RM ' . $ticket['room'] . '</td>
            <td data-cell="Request Category">' .  $request_type_name . '</td>
            <td data-cell="Current Status">' . $ticket["status"] . '</td>
            <td data-cell="Last Updated">' . $ticket["last_updated"] . '</td>
            <td data-cell="Due">' . $ticket["due_date"] . '</td>
            <td data-cell="Assigned">' . $ticket["employee"] . '</td>
        </tr>';
    }

    echo '</tbody></table>';
}
