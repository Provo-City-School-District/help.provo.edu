<?php
require_once('helpdbconnect.php');

function get_days_note_time(int $date_timestamp, string $user)
{
    global $database;

    $user_sanitized = trim(htmlspecialchars($user));
    $query = "SELECT created, date_override, time from notes WHERE creator = '$user_sanitized'";
    $query_result = mysqli_query($database, $query);

    $total_time = 0;
    while ($row = mysqli_fetch_assoc($query_result))  {
        $created = $row["created"];
        $date_override = $row["date_override"];
        $time = $row["time"];

        $used_date = $created;
        if ($date_override != null)
            $used_date = $date_override;

        $note_date = date("Y-m-d", strtotime($used_date));
        $input_date = date("Y-m-d", $date_timestamp);

        // Compare the date strings
        if ($note_date == $input_date) {
            $total_time += $time;
        }
    }

    return $total_time;
}