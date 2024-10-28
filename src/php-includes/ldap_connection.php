<?php

function get_ldaps_conn()
{
    $ldap_host = getenv('LDAPHOST');
    $ldap_port = getenv('LDAPPORT');

    $ldap_conn = ldap_connect("ldaps://dc1.psd.provo.edu:636");
    return $ldap_conn;
}