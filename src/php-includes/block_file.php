<?php
require_once 'helpdbconnect.php';
require_once 'functions.php';

function login_user($user_id, $username) {
    $_SESSION['username'] = $username;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['last_timestamp'] = time();

    // Query Local user information
    $local_query_results = HelpDB::get()->execute_query("SELECT * FROM user_settings WHERE user_id = ?", [$user_id]);
    $local_query_data = mysqli_fetch_assoc($local_query_results);

    // if user is still not found, LDAP failed to insert user into local database
    if ($local_query_data == null) {
        // print_r($_SESSION);
        $msg = "Error: User not found in local database.";
        log_app(LOG_ERR, $username . " Error: User not found in local database.");
        // unset session variables
        session_unset();
        session_destroy();
        $_SESSION = array();
        session_start();
        session_regenerate_id(true);
        // Set error message
        $_SESSION['current_status'] = $msg;
        $_SESSION['status_type'] = "error";
        // push back to login page
        header('Location: index.php');
        exit;
    }

    // Retrieve user's permissions from the users table
    $permissions = array(
        'can_view_tickets' => $local_query_data['can_view_tickets'],
        'can_create_tickets' => $local_query_data['can_create_tickets'],
        'can_edit_tickets' => $local_query_data['can_edit_tickets'],
        'is_admin' => $local_query_data['is_admin'],
        'is_tech' => $local_query_data['is_tech'],
        'is_supervisor' => $local_query_data['is_supervisor'],
        'is_intern' => $local_query_data['is_intern'],
        'intern_site' => $local_query_data['intern_site'],
        'supervisor_username' => $local_query_data['supervisor_username'],
        'is_location_manager' => $local_query_data['is_location_manager'],
        'location_manager_sitenumber' => $local_query_data['location_manager_sitenumber'],
        'can_see_all_techs' => $local_query_data['can_see_all_techs'],
    );
    // Set color scheme
    $_SESSION['color_scheme'] = $local_query_data['color_scheme'];
    $_SESSION['note_order'] = $local_query_data['note_order'];
    $_SESSION['hide_alerts'] = $local_query_data['hide_alerts'];
    $_SESSION['ticket_limit'] = $local_query_data['ticket_limit'];
    $_SESSION['department'] = $local_query_data['department'];
    // Store user's permissions in the session
    $_SESSION['permissions'] = $permissions;
}

//login_user(5, 'braxtona');

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

        $user_result = HelpDB::get()->execute_query('SELECT id, username FROM users WHERE gsso = ?', [$login_token]);
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
