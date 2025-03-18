<?php
require "ticket_utils.php";
if (session_is_intern()) {
    require from_root("/controllers/tickets/intern_tickets.php");
    exit;
}

require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/ticket_base_variables.php");

// get user setting for if to show alerts or not
$show_alerts = get_user_setting(get_id_for_user($_SESSION['username']), 'show_alerts');

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);
// include("ticket_utils.php");

$managed_location = $_SESSION['permissions']['location_manager_sitenumber'];
$department_managed = $_SESSION['permissions']['location_manager_sitenumber'];

//Alerts query
// $alerts_query = <<<alerts_query
// SELECT alerts.* 
// FROM alerts 
// JOIN users ON alerts.employee = users.username
// JOIN tickets ON alerts.ticket_id = tickets.id
// WHERE tickets.location = ?
// AND alerts.supervisor_alert IN (0, 1)
// alerts_query;

// $alerts_result = HelpDB::get()->execute_query($alerts_query, [$managed_location]);


//location tickets query
$location_tickets_query = <<<location_tickets
SELECT * 
FROM tickets 
WHERE (location = ? OR department = ?)
AND tickets.status NOT IN ('closed', 'resolved')

location_tickets;

$location_ticket_result = HelpDB::get()->execute_query($location_tickets_query, [$managed_location, $department_managed]);
$tickets = get_parsed_ticket_data($location_ticket_result);
//query for unassigned tickets for location
// $unassigned_ticket_query = <<<unassigned_tickets
// SELECT *
// FROM tickets
// WHERE status NOT IN ('closed', 'resolved') 
// AND (employee IS NULL OR employee = 'unassigned')
// AND location = ?
// unassigned_tickets;

// $unassigned_ticket_result = HelpDB::get()->execute_query($unassigned_ticket_query, [$managed_location]);

echo $twig->render('location_tickets.twig', [
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
    'tickets' => $tickets,
    'location_managed' => location_name_from_id($managed_location),
    // 'alerts' => $alerts,
    'hide_alerts' => $_SESSION['hide_alerts'],
    // 'show_alerts' => $show_alerts
    'show_alerts' => 1
]);
