<?php
require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/ticket_base_variables.php");
require_once "ticket_utils.php";



$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
	'cache' => from_root('/../twig-cache')
]);

$ticket_query = <<<QUERY
	SELECT t.*, GROUP_CONCAT(DISTINCT a.alert_level) AS alert_levels
    FROM tickets t
    LEFT JOIN alerts a ON t.id = a.ticket_id
    WHERE EXISTS (
        SELECT 1
        FROM notes n
        WHERE n.linked_id = t.id
        AND n.creator = ?
        AND n.created >= DATE_SUB(NOW(), INTERVAL 2 DAY)
    )
    OR EXISTS (
        SELECT 1
        FROM ticket_logs l
        WHERE l.ticket_id = t.id
        AND l.user_id = ?
        AND l.created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
    )
    GROUP BY t.id;
QUERY;

$ticket_result = HelpDB::get()->execute_query($ticket_query, [$username, $username]);
$tickets = get_parsed_ticket_data($ticket_result);

echo $twig->render('ticket_table_base.twig', [
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
	'tickets' => $tickets,
	'page_title' => 'Recent Tickets',
]);
