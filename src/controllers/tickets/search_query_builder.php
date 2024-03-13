<?php

// Construct the SQL query based on the selected search options
$ticket_query = "SELECT * FROM tickets WHERE 1=1 ";


if (!empty($search_name)) {
    // Split the search terms into an array of words
    $words = explode(" ", $search_name);
    $wordCount = count($words);

    $ticket_query .= "AND tickets.id IN (SELECT linked_id FROM notes WHERE ";
    for ($i = 0; $i < $wordCount; $i++) {
        $ticket_query .= "(name LIKE '%" . mysqli_real_escape_string($database, $words[$i]) . "%' OR description LIKE '%" . mysqli_real_escape_string($database, $words[$i]) . "%' OR note LIKE '%" . mysqli_real_escape_string($database, $words[$i]) . "%')";
        if ($i != $wordCount - 1) {
            $ticket_query .= " AND ";
        }
    }
    $ticket_query .= ")";
}

if (!empty($search_id)) {
    $ticket_query .= " AND id LIKE '" . $search_id . "'";
}


if (!empty($search_location)) {
    $ticket_query .= " AND location LIKE '%$search_location%'";
}
if (!empty($search_employee)) {
    if ($search_employee == 'Unassigned') {
        $ticket_query .= " AND (employee IS NULL OR employee = 'unassigned' OR employee = '')";
    } else {
        $ticket_query .= " AND employee LIKE '%$search_employee%'";
    }
}
if (!empty($search_priority)) {
    $ticket_query .= " AND priority LIKE '$search_priority'";
}
if (!empty($search_client)) {
    $ticket_query .= " AND client LIKE '%$search_client%'";
}
if (!empty($search_status)) {
    $ticket_query .= " AND status LIKE '%$search_status%'";
}
if (!empty($search_start_date) && !empty($search_end_date) && !empty($dates_searched)) {
    $search_end_date = date('Y-m-d', strtotime($search_end_date . ' +1 day'));

    $date_conditions = array();
    foreach ($dates_searched as $date) {
        $date_conditions[] = "($date BETWEEN '$search_start_date' AND '$search_end_date')";
    }
    $ticket_query .= ' AND (' . implode(' OR ', $date_conditions) . ')';
}
