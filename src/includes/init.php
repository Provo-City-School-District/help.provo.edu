<?php
// Checks if Session exists, if not starts one.
if (!session_id()) {
    session_start();
    // session_regenerate_id(true);
}
