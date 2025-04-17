<?php
// Check if the user agent is from InterMapper
if (strpos($user_agent, 'InterMapper') === false && !session_id()) {
    session_start();
}

// Ensure $_SESSION is an array
if (!isset($_SESSION)) {
    $_SESSION = [];
}

// Initialize last_timestamp if not set
if (!isset($_SESSION['last_timestamp'])) {
    $_SESSION['last_timestamp'] = time();
}
// Regenerate session ID only if more than 30 minutes has passed since the last regeneration
if (time() - $_SESSION['last_timestamp'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_timestamp'] = time(); // Update the timestamp after regeneration
}

// Update the last timestamp for the session
$_SESSION['last_timestamp'] = time();

// Ensure $_SESSION is an array
if (!isset($_SESSION)) {
    $_SESSION = [];
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
