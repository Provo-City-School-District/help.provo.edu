<?php
// Checks if Session exists, if not starts one.
if (!session_id()) {
    session_start();
}

// Reset cookie lifetime each time this is loaded. Included by header.php
$lifetime = 7200;
setcookie(session_name(), session_id(), time() + $lifetime);