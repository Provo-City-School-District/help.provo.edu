<?php
$root_domain = getenv('ROOTDOMAIN');
$is_logged_in = false;
// Checks if Session exists, if not starts one.
if (!session_id()) {
    session_start();
}

?>