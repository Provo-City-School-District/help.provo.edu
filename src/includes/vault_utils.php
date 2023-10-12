<?php

require("vaultdbconnect.php");

/*  Get vault id from barcode for URL
    
    Considered doing regex like WO# for loading tickets in edit_ticket.php but it has
    severe performance implications due to network request to get each ID
    Takes 0.5-1s extra to load a ticket for one lookup on BC#

    Maybe update vault to have an option to load from barcode instead?
    Then there would be no need to do this to generate a URL
*/
function get_vault_id_from_barcode(string $barcode)
{
    global $vault_db;

    $query = <<<STR
    SELECT
        id
    FROM
        assets
    WHERE
        barcode = '$barcode'
    STR;

    $result = mysqli_query($vault_db, $query);
    if (!$result) {
        die('Error: ' . mysqli_error($database));
    }

    $device = mysqli_fetch_assoc($result);

    return $device["id"];
}