<?php
require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/ticket_base_variables.php");
require_once "ticket_utils.php";



$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
	'cache' => from_root('/../twig-cache')
]);

$ticket_query = <<<QUERY
	SELECT tickets.*, GROUP_CONCAT(DISTINCT alerts.alert_level) AS alert_levels
	FROM tickets
	LEFT JOIN (
		SELECT linked_id
		FROM notes
		WHERE creator = ? AND created >= DATE_SUB(NOW(), INTERVAL 2 DAY)
	) AS recent_notes ON tickets.id = recent_notes.linked_id
	LEFT JOIN (
		SELECT ticket_id
		FROM ticket_logs
		WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
	) AS recent_logs ON tickets.id = recent_logs.ticket_id
	LEFT JOIN alerts ON tickets.id = alerts.ticket_id
	WHERE recent_notes.linked_id IS NOT NULL OR recent_logs.ticket_id IS NOT NULL
	GROUP BY tickets.id
QUERY;

$ticket_result = HelpDB::get()->execute_query($ticket_query, [$username, $username]);
$tickets = get_parsed_ticket_data($ticket_result);

echo $twig->render('recent_tickets.twig', [
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


	// ticket_table_base variables
	'tickets' => $tickets
]);
