<?php

function get_ldaps_conn()
{
    $ldap_primary_host = getenv('LDAP_PRIMARY_HOST');
    $ldap_port = getenv('LDAP_PORT');

    $ldap_conn = ldap_connect("ldaps://$ldap_primary_host:$ldap_port");
    $using_primary_host = true;

    // Check if URI syntax is good (per docs, that's all ldap_connect does, bind is needed to validate connection)
    if ($ldap_conn) {
        $ldap_user = getenv('LDAP_USER');
        $ldap_password = getenv('LDAP_PASS');

        $ldap_bind = ldap_bind($ldap_conn, $ldap_user, $ldap_password);
        if (!$ldap_bind) {
            $using_primary_host = false;
        }
    } else {
       $using_primary_host = false;
    }

    if (!$using_primary_host) {
        // Failed to bind to primary host. Try next one
        $ldap_secondary_host = getenv('LDAP_SECONDARY_HOST');

        // If this fails too, receiver will know. Return now
        $ldap_conn = ldap_connect("ldaps://$ldap_secondary_host:$ldap_port");
    }

    return $ldap_conn;
}