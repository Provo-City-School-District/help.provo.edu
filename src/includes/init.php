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
    $user_last_login = get_last_login_time($_SESSION['username']);
    $current_time = time();

    // Calculate the time difference in seconds
    $time_difference = $current_time - $user_last_login;

    if ($time_since_last_login > 4 * 60 * 60) {
        // Unset all session variables
        $_SESSION = array();
        log_app(LOG_INFO, $_SESSION['username'] . " Session killed for being older than 4 hours");
        // Delete the session cookie
        session_unset();
        // Destroy the session
        session_destroy();

        // Redirect to the login page
        header('Location: /index.php');
        exit;
    }

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
