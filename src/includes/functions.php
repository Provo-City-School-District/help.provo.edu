<?php
// handle 500 error
register_shutdown_function("handleFatalError");

function handleFatalError()
{
    $error = error_get_last();
    if ($error && $error['type'] === E_ERROR) {
        // Redirect to your custom 500 error page
        header('Location: /errors/500.html');
        exit;
    }
}

//Checks current page in $_SERVER variable.
function endsWith($haystack, $needle)
{
    return substr($haystack, -strlen($needle)) === $needle;
}

//limit characters in a string
function limitChars($string, $limit)
{
    if (strlen($string) > $limit) {
        $string = substr($string, 0, $limit) . '...';
    }
    return $string;
}
//simple validator that can be used for user input
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


function log_app(int $priority, string $message)
{
    openlog("appLog", LOG_PID | LOG_PERROR, LOG_LOCAL0);
    syslog($priority, $message);
    closelog();
}

function add_ticket_msg_id_mapping(string $message_id, int $ticket_id)
{
    global $database;

    if (isset($message_id)) {
        $insert_email_id_query = "INSERT INTO ticket_email_ids (email_id, ticket_id) VALUES (?, ?)";
        $insert_email_id_stmt = mysqli_prepare($database, $insert_email_id_query);

        mysqli_stmt_bind_param($insert_email_id_stmt, "si", $message_id, $ticket_id);
        mysqli_stmt_execute($insert_email_id_stmt);
        mysqli_stmt_close($insert_email_id_stmt);
        log_app(LOG_INFO, "Added $message_id to list with ticket id $ticket_id");
        return true;
    } else {
        log_app(LOG_ERR, "message_id was null. Not adding $ticket_id to ticket_email_ids");
        return false;
    }
}

function get_client_name(string $client)
{
    $ldap_host = getenv('LDAPHOST');
    $ldap_port = getenv('LDAPPORT');
    $ldap_dn = getenv('LDAP_DN');
    $ldap_user = getenv('LDAP_USER');
    $ldap_password = getenv('LDAP_PASS');

    $ldap_conn = ldap_connect($ldap_host, $ldap_port);
    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    $ldap_bind = ldap_bind($ldap_conn, $ldap_user, $ldap_password);

    if (!$ldap_bind) {
        die('Could not bind to LDAP server.');
    }

    $search = "(&(objectCategory=person)(objectClass=user)(samaccountname=$client))";
    $ldap_result = ldap_search($ldap_conn, $ldap_dn, $search);
    $entries = ldap_get_entries($ldap_conn, $ldap_result);

    // Should only be one match, get the first one
    $firstname = isset($entries[0]['givenname'][0]) ? $entries[0]['givenname'][0] : null;
    $lastname = isset($entries[0]['sn'][0]) ? $entries[0]['sn'][0] : null;
    $result = ['firstname' => $firstname, 'lastname' => $lastname];
    return $result;
}

function get_client_location(string $client)
{
    $ldap_host = getenv('LDAPHOST');
    $ldap_port = getenv('LDAPPORT');
    $ldap_dn = getenv('LDAP_DN');
    $ldap_user = getenv('LDAP_USER');
    $ldap_password = getenv('LDAP_PASS');

    $ldap_conn = ldap_connect($ldap_host, $ldap_port);
    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    $ldap_bind = ldap_bind($ldap_conn, $ldap_user, $ldap_password);

    if (!$ldap_bind) {
        die('Could not bind to LDAP server.');
    }

    $search = "(&(objectCategory=person)(objectClass=user)(samaccountname=$client))";
    $ldap_result = ldap_search($ldap_conn, $ldap_dn, $search);
    $entries = ldap_get_entries($ldap_conn, $ldap_result);

    // Should only be one match, get the first one, default to District Office
    $location_code = intval($entries[0]["ou"][0] ?: 38);

    // Hacky mapping for aux services, should be 1896 internally
    if ($location_code == 1892)
        return 1896;
    else
        return $location_code;
}

function find_clients(string $name)
{
    $ldap_host = getenv('LDAPHOST');
    $ldap_port = getenv('LDAPPORT');
    $ldap_dn = getenv('LDAP_DN');
    $ldap_user = getenv('LDAP_USER');
    $ldap_password = getenv('LDAP_PASS');

    $ldap_conn = ldap_connect($ldap_host, $ldap_port);
    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    $ldap_bind = ldap_bind($ldap_conn, $ldap_user, $ldap_password);

    if (!$ldap_bind) {
        die('Could not bind to LDAP server.');
    }

    $search = '(&(objectCategory=person)(objectClass=user)';
    if ($name !== '') {
        $search .= '(givenname=*' . $name . '*)';
    }
    $search .= ')';

    $ldap_result = ldap_search($ldap_conn, $ldap_dn, $search);
    $entries = ldap_get_entries($ldap_conn, $ldap_result);

    $results = [];
    for ($i = 0; $i < $entries['count']; $i++) {
        $username = $entries[$i]['samaccountname'][0];
        $firstname = $entries[$i]['givenname'][0];
        $lastname = $entries[$i]['sn'][0];
        $results[] = ['username' => $username, 'firstname' => $firstname, 'lastname' => $lastname];
    }

    return $results;
}

