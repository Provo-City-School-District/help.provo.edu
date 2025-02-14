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

    return CreateLocalUserStatus::Success;
}
