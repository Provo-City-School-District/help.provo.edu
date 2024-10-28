<?php

function get_ldaps_conn()
{
    ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_DEMAND);
    ldap_set_option(null, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option(null, LDAP_OPT_X_TLS_CACERTDIR, "/etc/ssl/certs");

    $ldap_host = getenv('LDAPHOST');
    $ldap_port = getenv('LDAPPORT');

    $ldap_conn = ldap_connect("ldaps://dc1.psd.provo.edu:636");
    return $ldap_conn;
}