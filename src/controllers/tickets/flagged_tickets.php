<?php
require from_root("/vendor/autoload.php");
require from_root("/new-controllers/ticket_base_variables.php");
require "ticket_utils.php";

session_start();

$loader = new \Twig\Loader\FilesystemLoader(from_root('/views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/twig-cache')
]);

$ticket_query = <<<STR
	SELECT tickets.*, GROUP_CONCAT(DISTINCT alerts.alert_level) AS alert_levels
	FROM tickets
	LEFT JOIN alerts ON tickets.id = alerts.ticket_id
	WHERE tickets.id IN (
		SELECT flagged_tickets.ticket_id 
		FROM flagged_tickets 
		WHERE flagged_tickets.user_id IN (
			SELECT users.id 
			FROM users 
			WHERE users.username = ?
		)
	)
	GROUP BY tickets.id
STR;

$ticket_result = $database->execute_query($ticket_query, [$username]);
$tickets = get_parsed_ticket_data($ticket_result);

echo $twig->render('flagged_tickets.twig', [
    // base variables
    'color_scheme' => $color_scheme,
    'current_year' => $current_year,
    'user_permissions' => $permissions,
    'wo_time' => $wo_time,
    'user_pref' => $user_pref,
    'ticket_limit' => $ticket_limit,
	'status_alert_type' => $status_alert_type,
	'status_alert_message' => $status_alert_message,

    // ticket_base variables
    'subord_count' => $subord_count,
	'num_assigned_tickets' => $num_assigned_tickets,
	'num_flagged_tickets' => $num_flagged_tickets,

	// ticket_table_base variables
	'tickets' => $tickets
]);