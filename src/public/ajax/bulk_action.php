<?php
require "block_file.php";
require "ticket_utils.php";
require "helpdbconnect.php";


$ticket_ids = $_POST["ticket_ids"];
$ticket_action = $_POST["ticket_action"];

log_app(LOG_INFO, $ticket_action);
foreach ($ticket_ids as $ticket_id) {
    log_app(LOG_INFO, $ticket_id);
}