<?php
require_once("block_file.php");
require_once("ticket_utils.php");
require_once("helpdbconnect.php");
require_once("ldap_connection.php");

$firstname = isset($_POST['firstname']) ? ldapspecialchars($_POST['firstname']) : '';
$lastname = isset($_POST['lastname']) ? ldapspecialchars($_POST['lastname']) : '';

$ldap_dn = getenv('LDAP_DN');
$ldap_user = getenv('LDAP_USER');
$ldap_password = getenv('LDAP_PASS');

$ldap_conn = get_ldaps_conn();
$ldap_bind = ldap_bind($ldap_conn, $ldap_user, $ldap_password);

if (!$ldap_bind) {
    die('Could not bind to LDAP server.');
}

$search = '(&(objectCategory=person)(objectClass=user)';
if ($firstname !== '') {
    $search .= '(givenname=*' . $firstname . '*)';
}
if ($lastname !== '') {
    $search .= '(sn=*' . $lastname . '*)';
}
$search .= ')';

$ldap_result = ldap_search($ldap_conn, $ldap_dn, $search);
$entries = ldap_get_entries($ldap_conn, $ldap_result);

$results = [];
for ($i = 0; $i < $entries['count']; $i++) {
    $username = $entries[$i]['samaccountname'][0];
    $firstname = $entries[$i]['givenname'][0];
    $lastname = $entries[$i]['sn'][0];
    $location = $entries[$i]['ou'][0] ?: "";
    if ($location == 1892)
        $location = 1896;
    $title = $entries[$i]['title'][0];
    $results[] = ['username' => strtolower($username), 'firstname' => $firstname, 'lastname' => $lastname, "location_name" => location_name_from_id($location), "title" => $title];
}

header('Content-Type: application/json');
echo json_encode($results);
