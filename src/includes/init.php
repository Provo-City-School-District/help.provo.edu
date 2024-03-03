<?php
// // List of normal web browser user agent substrings
// $normal_browsers = ["Mozilla", "Chrome", "Safari", "Opera", "MSIE", "Edge", "Firefox"];

// // Get the user agent
// $user_agent = $_SERVER['HTTP_USER_AGENT'];

// // Check if the user agent is a normal web browser
// $is_normal_browser = array_filter($normal_browsers, function ($browser) use ($user_agent) {
//     return strpos($user_agent, $browser) !== false;
// });

// Only start a session if the user agent is a normal web browser
if (!session_id()) {
    session_start();
}
include_once('functions.php');
include_once('helpdbconnect.php');


// Set the inactivity time
$inactivity_time = 60 * 60; // 60 minutes

// Check if the last_timestamp session variable is set
if (isset($_SESSION['last_timestamp'])) {
    $time_difference = calculateTimeSinceLastLogin();

    // Check if the user has been logged in for more than 3 hours
    if ($time_difference > 60 * 60 * 3) {
        // Unset all session variables
        $_SESSION = array();
        log_app(LOG_INFO, $_SESSION['username'] . " Session killed for being older than 3 hours");
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
