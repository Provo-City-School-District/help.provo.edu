<?php
require_once("helpdbconnect.php");

function user_exists_locally(string $username)
{
    global $database;

    $check_query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($database, $check_query);

    // If a row is returned, the user exists
    return mysqli_num_rows($result) > 0;
}

function create_user_in_local_db($username)
{
    global $database;

    $ldap_dn = getenv('LDAP_DN');
    $ldap_user = getenv('LDAP_USER');
    $ldap_password = getenv('LDAP_PASS');

    $ldap_host = getenv('LDAPHOST');
    $ldap_port = getenv('LDAPPORT');
    $ldap_conn = ldap_connect($ldap_host, $ldap_port);
    if (!$ldap_conn) {
        log_app(LOG_ERR, "Failed to create LDAP connection");
    }

    // anonymous bind
    $ldap_bind = ldap_bind($ldap_conn, $ldap_user, $ldap_password);
    if (!$ldap_bind) {
        log_app(LOG_ERR, "LDAP bind failed.");
    }

    $search = "(&(objectCategory=person)(objectClass=user)(sAMAccountName=$username))";
    $ldap_search_result = ldap_search($ldap_conn, $ldap_dn, $search);
    if (!$ldap_search_result) {
        log_app(LOG_ERR, 'LDAP search failed.');
    }
    
    $ldap_entries_result = ldap_get_entries($ldap_conn, $ldap_search_result);
    if (!$ldap_entries_result) {
        log_app(LOG_ERR, "LDAP get entries failed.");
    }

    if ($ldap_entries_result['count'] == 0) {
        log_app(LOG_ERR, "User '$username' was not found in LDAP");
    }

    for ($i = 0; $i < $ldap_entries_result['count']; $i++) {
        $username = $ldap_entries_result[$i]['samaccountname'][0];
        $firstname = $ldap_entries_result[$i]['givenname'][0];
        $lastname = $ldap_entries_result[$i]['sn'][0];
        $email = $ldap_entries_result[$i]['mail'][0];
        $employee_id = $ldap_entries_result[$i]['employeeid'][0];
    }

    $insert_query = "INSERT INTO users (username, email, lastname, firstname, ifasid) VALUES ('" . $username . "', '" . $email . "', '" . $lastname . "', '" . $firstname . "', '" . $employee_id . "')";

    $insert_result = mysqli_query($database, $insert_query);
    if (!$insert_result) {
        $current_mysqli_error = mysqli_error($database);
        log_app(LOG_ERR, "Insert query error: $current_mysqli_error");
    }
}