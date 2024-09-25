<?php
require_once('helpdbconnect.php');
require_once("block_file.php");
require_once("ticket_utils.php");
require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/ticket_base_variables.php");

if (!session_id())
	session_start();

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
	'cache' => from_root('/../twig-cache')
]);

// Tickets query
$ticket_query = <<<STR
	(SELECT * FROM tickets WHERE client = ?)
		UNION
		(SELECT tickets.* FROM tickets 
		JOIN notes ON tickets.id = notes.linked_id 
		WHERE notes.creator = ?)
		ORDER BY last_updated DESC
STR;

$ticket_result = HelpDB::get()->execute_query($ticket_query, [$username, $username]);
$ticket_data = get_parsed_ticket_data($ticket_result);

echo $twig->render('ticket_history.twig', [
	// base variables
	'color_scheme' => $color_scheme,
	'current_year' => $current_year,
	'user_permissions' => $permissions,
	'wo_time' => $wo_time,
	'user_pref' => $user_pref,
	'ticket_limit' => $ticket_limit,
	'status_alert_type' => $status_alert_type,
	'status_alert_message' => $status_alert_message,
	'app_version' => $app_version,

	// ticket_base variables
	'subord_count' => $subord_count,
	'num_assigned_tickets' => $num_assigned_tickets,
	'num_flagged_tickets' => $num_flagged_tickets,
    'num_assigned_intern_tickets' => $num_assigned_intern_tickets,
    'num_assigned_tasks' => $num_assigned_tasks,
    'num_subordinate_tickets' => $num_subordinate_tickets,


	// tickets variables
	'tickets' => $ticket_data
]);
