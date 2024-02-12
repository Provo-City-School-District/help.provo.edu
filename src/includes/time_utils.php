<?php
require_once('helpdbconnect.php');

/*
Inputs: string of username, array of day unix timestamps to add up note totals for
Output: array of note totals for in the same indexing order as the input array (days)
*/
function get_note_time_for_days(string $user, array $days)
{
    global $database;

    $times = [];
    // set times to 0
    for ($i = 0; $i < count($days); $i++)
        $times[$i] = 0;

    $user_sanitized = mysqli_real_escape_string($database, $user);

    // Hasn't been fully battle tested but should work fine
    $query =
        <<<STR
    SELECT 
        created, date_override, work_hours, work_minutes, travel_hours, travel_minutes FROM notes
    WHERE 
        creator = '$user_sanitized' AND
        (
            CASE WHEN date_override IS NULL THEN
                created >= DATE_SUB(curdate(), INTERVAL 1 WEEK) ELSE
                date_override >= DATE_SUB(curdate(), INTERVAL 1 WEEK)
            END
        )   
    STR;

    $query_result = mysqli_query($database, $query);
    log_app(LOG_INFO, "Parsing tickets for '$user_sanitized'");
    while ($row = mysqli_fetch_assoc($query_result)) {
        $created = $row["created"];
        $date_override = $row["date_override"];

        $time = ($row["travel_hours"] + $row["work_hours"]) * 60;
        $time += $row["travel_minutes"] + $row["work_minutes"];

        $used_date = $created;
        if ($date_override != null)
            $used_date = $date_override;

        $note_date = date("Y-m-d", strtotime($used_date));

        // Compare the date strings
        for ($i = 0; $i < count($days); $i++) {
            $input_date = date("Y-m-d", $days[$i]);
            if ($note_date == $input_date) {
                $times[$i] += $time;
            }
        }
    }

    return $times;
}
