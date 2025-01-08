<?php
if (!session_id()) {
    session_start();
}

// Check if 'username' exists in the session
if (empty($_SESSION['username'])) {
    // Store the requested page in the session, checking if 'REQUEST_URI' exists
    if (isset($_SERVER['REQUEST_URI'])) {
        $_SESSION['requested_page'] = $_SERVER['REQUEST_URI'];
    }

    // Redirect to the login page
    header('Location:' . getenv('ROOTDOMAIN'));
    exit;
}
