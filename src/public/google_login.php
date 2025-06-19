<?php
require(from_root('/../vendor/autoload.php'));
require("authentication_utils.php");
require "ticket_utils.php";

function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $randomString;
}

session_start();
session_regenerate_id(true);

// init configuration 
$clientID = getenv("GOOG_SSO_ID");
$clientSecret = getenv("GOOG_SSO_SECRET");
$redirectUri = getenv("GOOG_SSO_REDIRECT");

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
// $client->setAccessType('offline');
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    // Get Token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    // Check if fetching token did not return any errors
    if (!isset($token['error'])) {
        $user_ucode = $_GET['code'];
        // Setting Access token
        $client->setAccessToken($token['access_token']);

        // Get Account Profile using Google Service
        $gservice = new Google_Service_Oauth2($client);

        $login_data = [];
        // Get User Data
        $udata = $gservice->userinfo->get();
        foreach ($udata as $k => $v) {
            $login_data['login_' . $k] = $v;
        }
        //$_SESSION['ucode'] = $user_ucode;


        $email = $login_data['login_email'];
        $email = strtolower($email);
        $email = trim($email);
        $username = strtolower(str_replace('@provo.edu', '', $email));

        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = get_id_for_user($_SESSION["username"]);
        $_SESSION['last_timestamp'] = time();

        // Check if the user is authorized to use the helpdesk by checking if they are using a provo.edu email address
        if (!isset($email) || strpos($email, '@provo.edu') === false) {
            $msg = "Error: Invalid email address or Google SSO code not found.";
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

        if (!user_exists_locally($username)) {
            create_user_in_local_db($username);
        }
        // Query Local user information
        $local_query_results = HelpDB::get()->execute_query("SELECT * FROM user_settings WHERE user_id = ?", [$_SESSION["user_id"]]);
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
            'view_stats' => $local_query_data['view_stats'],
        );
        // Set color scheme
        $_SESSION['color_scheme'] = $local_query_data['color_scheme'];
        $_SESSION['note_order'] = $local_query_data['note_order'];
        $_SESSION['hide_alerts'] = $local_query_data['hide_alerts'];
        $_SESSION['ticket_limit'] = $local_query_data['ticket_limit'];
        $_SESSION['department'] = $local_query_data['department'];
        // Store user's permissions in the session
        $_SESSION['permissions'] = $permissions;

        $loc = get_client_location($username);
        // Update login timestamp and add google sso code to user record.
        $update_stmt = HelpDB::get()->prepare("UPDATE users SET last_login = NOW(), gsso = ?, ldap_location = ? WHERE email = ?");

        $login_token = hash('sha256', $_SESSION['user_id'] . $_SERVER['HTTP_USER_AGENT'] . time() . generateRandomString());
        $update_stmt->bind_param("sis", $login_token, $loc, $email);
        $update_stmt->execute();

        setcookie("COOKIE_REMEMBER_ME", $login_token, time() + (86400 * 7), "/", "", false, true);

        // Store the last login time in the session
        $_SESSION['last_login'] = date("Y-m-d H:i:s");
        if ($update_stmt === false) {
            $error_message = 'Execute failed: (' . HelpDB::get()->errno . ') ' . HelpDB::get()->error;
            error_log($error_message, 0);
        }

        // Log the successful login
        $loginMessage = "Successful login using Google SSO for username: " .  $username . " IP: " . $_SERVER["REMOTE_ADDR"] . " at " . date("Y-m-d H:i:s") . "\n";
        error_log($loginMessage, 0);

        if (isset($_SESSION['requested_page'])) {
            header('Location: ' . $_SESSION['requested_page']);
            unset($_SESSION['requested_page']);
        } else {
            header('Location: tickets.php');
        }
        exit;
    }
}

$authUrl = $client->createAuthUrl();
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;
