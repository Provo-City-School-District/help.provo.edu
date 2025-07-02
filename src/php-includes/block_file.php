<?php
require_once 'helpdbconnect.php';
require_once 'functions.php';
require_once 'authentication_utils.php';

// Get the user agent
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

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

    // attempt login if remember me is set
    if (isset($_COOKIE['COOKIE_REMEMBER_ME'])) {
        log_app(LOG_INFO, "Attempting to login from cookie");
        $login_token = $_COOKIE['COOKIE_REMEMBER_ME'];

        $user_result = HelpDB::get()->execute_query(
            'SELECT id, username FROM users WHERE (remember_me_token = ? AND last_login >= NOW() - INTERVAL 7 DAY)',
            [$login_token]
        );


        // found user, log them in
        if ($user_result->num_rows == 1) {
            session_start();
            $data = $user_result->fetch_assoc();
            $user_id = $data["id"];
            $username = $data["username"];
            login_user($user_id, $username);
        } else {
            // Redirect to the login page
            header('Location:' . getenv('ROOTDOMAIN'));
            exit;
        }
    } else {
        // Redirect to the login page
        header('Location:' . getenv('ROOTDOMAIN'));
        exit;
    }
}
