<?php
// Checks if Session exists, if not starts one.
if (!session_id()) {
    session_start();
}

function log_app(int $priority, string $message)
{
    openlog("appLog", LOG_PID | LOG_PERROR, LOG_LOCAL0);
    syslog($priority, $message);
    closelog();
}


// Set the inactivity time
$inactivity_time = 60 * 60; // 60 minutes


// Update the last_timestamp session variable
$_SESSION['last_timestamp'] = time();
