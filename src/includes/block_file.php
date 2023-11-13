<?php
if (!session_id()) {
    session_start();
}

// check if logged in. block access otherwise
if (!$_SESSION['username']) {
    http_response_code(404);
    include('404.php');
    exit;
}

?>