<?php
require_once('helpdbconnect.php');

function get_day_note_time_for_user(date $date, string $user)
{
    $user_sanitized = real_mysql_escape_string($user);
    $query = "SELECT created, date_override, time from notes WHERE creator = '$user_sanitized'";
    mysqli_query($database, $query);

    while ($row = mysqli_fetch_assoc($query_result))  {
        $created = $row["created"];
        $date_override = $row["date_override"];

        $used_date = $created;
        if ($date_override != null)
            $used_date = $date_override;

        $time = $row["time"];
    }
}