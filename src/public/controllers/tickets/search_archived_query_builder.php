<?php

// Construct the SQL query for the old ticket database
$old_ticket_query = "SELECT CONCAT('A-', JOB_TICKET_ID) AS a_id,PROBLEM_TYPE_ID,SUBJECT,QUESTION_TEXT,REPORT_DATE,LAST_UPDATED,JOB_TIME,ASSIGNED_TECH_ID,ROOM,LOCATION_ID,STATUS_TYPE_ID,CLOSE_DATE,CLIENT_ID FROM whd.job_ticket";
if (!empty($search_id) || !empty($search_name) || !empty($search_location) || !empty($search_employee) || !empty($search_client) || !empty($search_status) || !empty($search_status) || !empty($search_priority) || !empty($dates_searched) || !empty($search_end_date) || !empty($search_start_date)) {
    $old_ticket_query .= " WHERE 1=1";
} else {
    $old_ticket_query .= " WHERE 1=0";
}

if (!empty($search_name)) {
    // Split the search terms into an array of words
    $words = explode(" ", $search_name);
    $wordCount = count($words);

    $old_ticket_query .= " AND JOB_TICKET_ID IN (SELECT JOB_TICKET_ID FROM tech_note WHERE ";
    for ($i = 0; $i < $wordCount; $i++) {
        $old_ticket_query .= "QUESTION_TEXT LIKE '%" . mysqli_real_escape_string(HelpDB::get(), $words[$i]) . "%' OR SUBJECT LIKE '%" . mysqli_real_escape_string(HelpDB::get(), $words[$i]) . "%' OR NOTE_TEXT LIKE '%" . mysqli_real_escape_string(HelpDB::get(), $words[$i]) . "%'";
        if ($i != $wordCount - 1) {
            $old_ticket_query .= " AND ";
        }
    }
    $old_ticket_query .= ")";
}

if (!empty($search_id)) {
    $search_id = intval($search_id);
    $old_ticket_query .= " AND JOB_TICKET_ID LIKE '$search_id'";
}
$invalid_ids_legacy_system = [1897, 381];
if (!empty($search_location) && !in_array($search_location, $invalid_ids_legacy_system)) {
    $old_ticket_query .= " AND LOCATION_ID IN (" . implode(",", $archived_location_ids) . ")";
}
if (!empty($search_employee)) {
    if ($search_employee == 'Unassigned') {
        $search_employee = "helpdesk";
    }
    // First, perform a query to get the CURRENT_DASHBOARD_ID for the given USERNAME
    $employee_query = "SELECT CURRENT_DASHBOARD_ID FROM whd.tech WHERE USER_NAME = ?";
    $employee_stmt = SolarWindsDB::get()->prepare($employee_query);
    $employee_stmt->bind_param('s', $search_employee);
    $employee_stmt->execute();
    $employee_result = $employee_stmt->get_result();
    $employee_row = $employee_result->fetch_assoc();
    $employee_id = $employee_row['CURRENT_DASHBOARD_ID'];

    // Now, you can use $employee_id in your main query to get the tickets assigned to the given tech
    $old_ticket_query .= " AND ASSIGNED_TECH_ID = $employee_id";
}
if (!empty($search_client)) {
    $old_ticket_query .= " AND CLIENT_ID LIKE '%$search_client%'";
}
$statusMap = [
    'open' => 1,
    'closed' => 3,
    'resolved' => 5,
    'pending' => 7,
    'maintenance' => 11,
    'vendor' => 12
];

// map old system status ids to our current system
$search_status_id = $statusMap[$search_status] ?? null;


if (!empty($search_status)) {
    $old_ticket_query .= " AND STATUS_TYPE_ID  LIKE '%$search_status_id%'";
}
// switch them back to the new system status names
$search_status = array_search($search_status_id, $statusMap);

if (!empty($search_start_date) && !empty($search_end_date) && !empty($dates_searched)) {

    $date_conditions = array();
    foreach ($dates_searched as $date) {
        switch ($date) {
            case 'due_date':
                $date = 'CLOSE_DATE';
                break;
            case 'created':
                $date = 'REPORT_DATE';
                break;
            case 'last_updated':
                $date = 'LAST_UPDATED';
                break;
            default:
                $date = null;
        }
        $date_conditions[] = "($date BETWEEN '$search_start_date' AND '$search_end_date')";
    }
    $old_ticket_query .= ' AND (' . implode(' OR ', $date_conditions) . ')';
}
