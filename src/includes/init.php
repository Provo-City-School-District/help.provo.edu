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


