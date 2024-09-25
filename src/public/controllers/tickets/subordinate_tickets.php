<?php
require_once('helpdbconnect.php');
require_once "block_file.php";
require_once "ticket_utils.php";
require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/ticket_base_variables.php");

if (!session_id())
	session_start();

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
	'cache' => from_root('/../twig-cache')
]);


if ($_SESSION['permissions']['is_supervisor'] != 1) {
	echo 'You do not have permission to view tickets.';
	exit;
}

// Tickets query
$ticket_query = <<<STR
	SELECT tickets.*, GROUP_CONCAT(DISTINCT alerts.alert_level) AS alert_levels
	FROM tickets
	JOIN users ON tickets.employee = users.username
	LEFT JOIN alerts ON tickets.id = alerts.ticket_id
	WHERE users.supervisor_username = ?
	AND tickets.status NOT IN ('closed', 'resolved')
	GROUP BY tickets.id
	ORDER BY tickets.last_updated DESC
STR;

$ticket_result = HelpDB::get()->execute_query($ticket_query, [$username]);
$ticket_data = get_parsed_ticket_data($ticket_result);

// Alerts query
$alerts_query = <<<STR
	SELECT alerts.* 
	FROM alerts 
	JOIN users ON alerts.employee = users.username
	WHERE users.supervisor_username = ?
	AND alerts.supervisor_alert IN (0, 1)
STR;

$alerts_result = HelpDB::get()->execute_query($alerts_query, [$username]);
$alerts = get_parsed_alert_data($alerts_result);

echo $twig->render('subordinate_tickets.twig', [
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
    
	// tickets variables
	'tickets' => $ticket_data,
	'alerts' => $alerts,
	'hide_alerts' => $_SESSION['hide_alerts']
]);
