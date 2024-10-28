<?php
require("helpdbconnect.php");
require("block_file.php");
require("functions.php");
require("ticket_utils.php");
require("ldap_connection.php");

$input = isset($_GET['name']) ? ldapspecialchars($_GET['name']) : '';
log_app(LOG_INFO, "input: ".$input);


$ldap_host = getenv('LDAPHOST');
$ldap_port = getenv('LDAPPORT');
$ldap_dn = getenv('LDAP_DN');
$ldap_archived_dn = getenv('LDAP_ARCHIVED_DN');
$ldap_user = getenv('LDAP_USER');
$ldap_password = getenv('LDAP_PASS');

$ldap_conn = get_ldaps_conn();
ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
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

$ldap_result_archived = ldap_search($ldap_conn, $ldap_archived_dn, $search);
$entries_archived = ldap_get_entries($ldap_conn, $ldap_result_archived);

$results = [];
for ($i = 0; $i < $entries['count']; $i++) {
    $samaccountname = $entries[$i]['samaccountname'][0] ?: null;
    $firstname = $entries[$i]['givenname'][0] ?: null;
    $lastname = $entries[$i]['sn'][0] ?: null;

    $location_code = 38;
    if (array_key_exists("ou", $entries[$i])) {
        $location_code = intval($entries[$i]['ou'][0] ?: 38);
    }

    // Hacky mapping for aux services, should be 1896 internally
    if ($location_code == 1892)
        $location_code = 1896;

    $results[] = ['username' => strtolower($samaccountname), 'firstName' => $firstname, 'lastName' => $lastname, 'location' => location_name_from_id($location_code)];
}

for ($i = 0; $i < $entries_archived['count']; $i++) {
    $samaccountname = $entries_archived[$i]['samaccountname'][0] ?: null;
    $firstname = $entries_archived[$i]['givenname'][0] ?: null;
    $lastname = $entries_archived[$i]['sn'][0] ?: null;

    $location_code = 38;
    if (array_key_exists("ou", $entries_archived[$i])) {
        $location_code = intval($entries_archived[$i]['ou'][0] ?: 38);
    }

    // Hacky mapping for aux services, should be 1896 internally
    if ($location_code == 1892)
        $location_code = 1896;

    $results[] = ['username' => strtolower($samaccountname), 'firstName' => $firstname, 'lastName' => $lastname, 'location' => "inactive"];
}

header('Content-Type: application/json');
echo json_encode($results);
?>