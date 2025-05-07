<?php
require_once("helpdbconnect.php");
require_once("block_file.php");
require_once("functions.php");
require_once("ticket_utils.php");
require_once("ldap_connection.php");

$input = isset($_GET['input']) ? ldapspecialchars($_GET['input']) : '';
log_app(LOG_INFO, "input: " . $input);


$ldap_dn = getenv('LDAP_DN');
$ldap_user = getenv('LDAP_USER');
$ldap_password = getenv('LDAP_PASS');

$ldap_conn = get_ldaps_conn();
$ldap_bind = ldap_bind($ldap_conn, $ldap_user, $ldap_password);

if (!$ldap_bind) {
    die('Could not bind to LDAP server.');
}

$input_split = explode(' ', $input);

if (count($input_split) == 2) {
    $input_first_name = $input_split[0];
    $input_last_name = $input_split[1];
    $search = "(&(objectCategory=person)(objectClass=user)(givenname=$input_first_name*)(sn=$input_last_name*))";
} else if (count($input_split) == 1) {
    $search = "(&(objectCategory=person)(objectClass=user)(|(givenname=$input*)(sn=$input*)))";
} else {
    die;
}

$ldap_result = ldap_search($ldap_conn, $ldap_dn, $search);
$entries = ldap_get_entries($ldap_conn, $ldap_result);

$results = [];
for ($i = 0; $i < $entries['count']; $i++) {
    $email = $entries[$i]['mail'][0] ?: null;
    $firstname = $entries[$i]['givenname'][0] ?: null;
    $lastname = $entries[$i]['sn'][0] ?: null;

    $location_code = 38;
    if (array_key_exists("ou", $entries[$i])) {
        $location_code = intval($entries[$i]['ou'][0] ?: 38);
    }

    // Hacky mapping for aux services, should be 1896 internally
    if ($location_code == 1892)
        $location_code = 1896;

    $results[] = ['email' => $email, 'firstName' => $firstname, 'lastName' => $lastname, 'location' => location_name_from_id($location_code)];
}

header('Content-Type: application/json');
echo json_encode($results);
