<?php
// Checks if Session exists, if not starts one.
if (!session_id()) {
    session_set_cookie_params(7200); // 2 hours until auto logout
    session_start();
    session_regenerate_id(true);
}
