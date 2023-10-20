<?php
require_once('helpdbconnect.php');

function get_day_note_time_for_user(date $date, string $user)
{
    $user_sanitized = real_mysql_escape_string($user);
    $query = "SELECT created, time from notes WHERE creator = '$user_sanitized'";
    mysqli_query($database, $query);

    while ($row = mysqli_fetch_assoc($query_result))  {
        $created = $row["created"];
        $time = $row["time"];
        // TODO check if day matches the passed in date
        // ALSO TODO: Shouldn't rely on created, there will need to be a date override so we will need a new column for the effective date too
    }
}