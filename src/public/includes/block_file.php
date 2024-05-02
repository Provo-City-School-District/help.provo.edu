<?php
if (!session_id()) {
    session_start();
}

// check if logged in. redirects to login page if not
if (!$_SESSION['username']) {
    // Store the requested page in the session
    $_SESSION['requested_page'] = $_SERVER['REQUEST_URI'];

    header('Location:' . getenv('ROOTDOMAIN'));
    exit;
}

?>