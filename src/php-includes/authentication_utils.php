<?php
require_once "helpdbconnect.php";
require_once "functions.php";
require_once "ldap_connection.php";

enum CreateLocalUserStatus
{
    case LDAPConnectFailed;
    case LDAPBindFailed;
    case LDAPSearchFailed;
    case LDAPGetEntriesFailed;
    case UsernameNotFound;
    case InsertQueryFailed;
    case UserAlreadyExists;
    case Success;
}

function user_exists_remotely($username)
{
    $ldap_dn = getenv('LDAP_DN');
    $ldap_user = getenv('LDAP_USER');
    $ldap_password = getenv('LDAP_PASS');


    $ldap_conn = get_ldaps_conn();
    if (!$ldap_conn) {
        log_app(LOG_ERR, "Failed to create LDAP connection");
        return false;
    }

    // Use our credentials to add it
    $ldap_bind = ldap_bind($ldap_conn, $ldap_user, $ldap_password);
    if (!$ldap_bind) {
        log_app(LOG_ERR, "LDAP bind failed.");
        return false;
    }

    $search = "(&(objectCategory=person)(objectClass=user)(sAMAccountName=$username))";
    $ldap_search_result = ldap_search($ldap_conn, $ldap_dn, $search);
    if (!$ldap_search_result) {
        log_app(LOG_ERR, 'LDAP search failed.');
        return false;
    }

    $ldap_entries_result = ldap_get_entries($ldap_conn, $ldap_search_result);
    if (!$ldap_entries_result) {
        log_app(LOG_ERR, "LDAP get entries failed.");
        return false;
    }

    if ($ldap_entries_result['count'] == 0) {
        log_app(LOG_ERR, "User '$username' was not found in LDAP");
        return false;
    }

    return true;
}

// Returns CreateLocalUserStatus depending on error (or success)
function create_user_in_local_db($username)
{
    $ldap_dn = getenv('LDAP_DN');
    $ldap_user = getenv('LDAP_USER');
    $ldap_password = getenv('LDAP_PASS');

    $ldap_conn = get_ldaps_conn();
    if (!$ldap_conn) {
        log_app(LOG_ERR, "Failed to create LDAP connection");
        return CreateLocalUserStatus::LDAPConnectFailed;
    }

    // Use our credentials to add it
    $ldap_bind = ldap_bind($ldap_conn, $ldap_user, $ldap_password);
    if (!$ldap_bind) {
        log_app(LOG_ERR, "LDAP bind failed.");
        return CreateLocalUserStatus::LDAPBindFailed;
    }

    $search = "(&(objectCategory=person)(objectClass=user)(sAMAccountName=$username))";
    $ldap_search_result = ldap_search($ldap_conn, $ldap_dn, $search);
    if (!$ldap_search_result) {
        log_app(LOG_ERR, 'LDAP search failed.');
        return CreateLocalUserStatus::LDAPSearchFailed;
    }

    $ldap_entries_result = ldap_get_entries($ldap_conn, $ldap_search_result);
    if (!$ldap_entries_result) {
        log_app(LOG_ERR, "LDAP get entries failed.");
        return CreateLocalUserStatus::LDAPGetEntriesFailed;
    }

    if ($ldap_entries_result['count'] == 0) {
        log_app(LOG_ERR, "User '$username' was not found in LDAP");
        return CreateLocalUserStatus::UsernameNotFound;
    }

    for ($i = 0; $i < $ldap_entries_result['count']; $i++) {
        $username = $ldap_entries_result[$i]['samaccountname'][0];
        $firstname = $ldap_entries_result[$i]['givenname'][0];
        $lastname = $ldap_entries_result[$i]['sn'][0];
        $email = $ldap_entries_result[$i]['mail'][0];
        $employee_id = $ldap_entries_result[$i]['employeeid'][0];
    }
    $username = strtolower($username);

    if (user_exists_locally($username))
        return CreateLocalUserStatus::UserAlreadyExists;

    $insert_query = "INSERT INTO users (username, email, lastname, firstname, ifasid) VALUES (?, ?, ?, ?, ?)";
    $insert_result = HelpDB::get()->execute_query($insert_query, [$username, $email, $lastname, $firstname, $employee_id]);

    if (!$insert_result) {
        $current_mysqli_error = mysqli_error(HelpDB::get());
        log_app(LOG_ERR, "Insert query error: $current_mysqli_error");
        return CreateLocalUserStatus::InsertQueryFailed;
    }
    // Insert into user_settings table
    $user_id = HelpDB::get()->insert_id;
    $insert_settings_query = "INSERT INTO user_settings (user_id) VALUES (?)";
    $insert_settings_result = HelpDB::get()->execute_query($insert_settings_query, [$user_id]);

    if (!$insert_settings_result) {
        $current_mysqli_error = mysqli_error(HelpDB::get());
        log_app(LOG_ERR, "Insert query error for user_settings: $current_mysqli_error");
        return CreateLocalUserStatus::InsertQueryFailed;
    }
    return CreateLocalUserStatus::Success;
}




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