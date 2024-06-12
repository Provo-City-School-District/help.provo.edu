<?php
// Get the user agent
$user_agent = $_SERVER['HTTP_USER_AGENT'];

// Check if the user agent is from InterMapper
if (strpos($user_agent, 'InterMapper') === false && !session_id()) {
    session_start();
}
include_once('functions.php');
include_once('helpdbconnect.php');


// Set the inactivity time
// $inactivity_time = 60 * 60; // 60 minutes

// Check if the last_timestamp session variable is set
// if (isset($_SESSION['last_timestamp'])) {
//     $time_difference = calculateTimeSinceLastLogin();

//     // Check if the user has been logged in for more than 3 hours
//     if ($time_difference > 60 * 60 * 3) {
//         // Unset all session variables
//         $logMessage =  $_SESSION['username'] . " Session killed for being older than 3 hours";
//         $_SESSION = array();
//         log_app(LOG_INFO, $logMessage);
//         // Delete the session cookie
//         session_unset();
//         // Destroy the session
//         session_destroy();

//         // Redirect to the login page
//         header('Location: /index.php');
//         exit;
//     }
//     $current_time = time();
//     // Calculate the inactivity time
//     $inactivity = $current_time - $_SESSION['last_timestamp'];

//     // Print the current time, the last timestamp, and the inactivity time
//     // log_app(LOG_INFO, 'Current time: ' . time());
//     // log_app(LOG_INFO, 'Last timestamp: ' . $_SESSION['last_timestamp']);
//     // log_app(LOG_INFO, 'Inactivity time: ' . $inactivity);

//     // Check if the user has been inactive for too long
//     if ($inactivity > $inactivity_time) {

//         // Print the current time, the last timestamp, and the inactivity time
//         log_app(LOG_INFO, 'Current time: ' . time());
//         log_app(LOG_INFO, 'Last timestamp: ' . $_SESSION['last_timestamp']);
//         log_app(LOG_INFO, 'Inactivity time: ' . $inactivity);
//         log_app(LOG_INFO, $_SESSION['username'] . 'User has been inactive for too long');

//         // Unset all session variables
//         $_SESSION = array();

//         // Delete the session cookie
//         session_unset();
//         // Destroy the session
//         session_destroy();

//         // Redirect to the login page
//         header('Location: /index.php');
//         exit;
//     }
// }

// Update the last_timestamp session variable
// $_SESSION['last_timestamp'] = time();
