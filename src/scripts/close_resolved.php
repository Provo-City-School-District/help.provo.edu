<?php

require_once('helpdbconnect.php');
require_once('functions.php');

log_app(LOG_INFO, "close_resolved.php running");

// Prepare a SQL statement to select tickets
$close_resolved_query = "UPDATE help.tickets SET status = 'closed' WHERE status = 'resolved' AND last_updated < NOW() - INTERVAL 10 DAY";
$close_resolved_stmt = $database->prepare($close_resolved_query);
$close_resolved_stmt->execute();
