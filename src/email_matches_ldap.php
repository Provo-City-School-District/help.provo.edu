<?php
require("helpdbconnect.php");
require("block_file.php");
require("functions.php");
require("ticket_utils.php");

$email = isset($_GET['email']) ? $_GET['email'] : '';
log_app(LOG_INFO, "email: ".$email);


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


$search = "(&(objectCategory=person)(objectClass=user)(mail=$email*))";
$ldap_result = ldap_search($ldap_conn, $ldap_dn, $search);
$entries = ldap_get_entries($ldap_conn, $ldap_result);

$results = [];
for ($i = 0; $i < $entries['count']; $i++) {
    $email = $entries[$i]['mail'][0] ?: null;
    $firstname = $entries[$i]['givenname'][0] ?: null;
    $lastname = $entries[$i]['sn'][0] ?: null;
    $location_code = intval($entries[$i]["ou"][0] ?: 38);

    // Hacky mapping for aux services, should be 1896 internally
    if ($location_code == 1892)
        $location_code = 1896;

    $results[] = ['email' => $email, 'firstName' => $firstname, 'lastName' => $lastname, 'location' => location_name_from_id($location_code)];
}

header('Content-Type: application/json');
echo json_encode($results);
?>