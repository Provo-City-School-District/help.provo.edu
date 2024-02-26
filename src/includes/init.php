<?php
include_once('functions.php');
include_once('helpdbconnect.php');
// Checks if Session exists, if not starts one.
if (!session_id()) {
    session_start();
}

// Set the inactivity time
$inactivity_time = 60 * 60; // 60 minutes

// Check if the last_timestamp session variable is set
if (isset($_SESSION['last_timestamp'])) {
    $current_time = time();
    // Calculate the inactivity time
    $inactivity = $current_time - $_SESSION['last_timestamp'];

    // Print the current time, the last timestamp, and the inactivity time
    log_app(LOG_INFO, 'Current time: ' . time());
    log_app(LOG_INFO, 'Last timestamp: ' . $_SESSION['last_timestamp']);
    log_app(LOG_INFO, 'Inactivity time: ' . $inactivity);

    // Check if the user has been inactive for too long
    if ($inactivity > $inactivity_time) {

        // Print the current time, the last timestamp, and the inactivity time
        log_app(LOG_INFO, 'Current time: ' . time());
        log_app(LOG_INFO, 'Last timestamp: ' . $_SESSION['last_timestamp']);
        log_app(LOG_INFO, 'Inactivity time: ' . $inactivity);
        log_app(LOG_INFO, 'User has been inactive for too long');

        // Unset all session variables
        $_SESSION = array();

        // Delete the session cookie
        session_unset();
        // Destroy the session
        session_destroy();

        // Redirect to the login page
        header('Location: /index.php');
        exit;
    }
}

// Update the last_timestamp session variable
$_SESSION['last_timestamp'] = time();
