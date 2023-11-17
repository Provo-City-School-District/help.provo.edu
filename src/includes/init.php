<?php
// Checks if Session exists, if not starts one.
if (!session_id()) {
    session_start();
    // Regenerate the session ID to mitigate the risk of session fixation.
    session_regenerate_id(true);
}
