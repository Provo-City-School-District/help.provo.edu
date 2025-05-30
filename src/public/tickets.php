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

$tech_ticket_query = <<<STR
    SELECT tickets.*, 
           GROUP_CONCAT(DISTINCT 
               CASE 
                   WHEN alerts.supervisor_alert = 0 THEN alerts.alert_level 
                   WHEN tickets.id IN (
                       SELECT ticket_id 
                       FROM ticket_tasks 
                       WHERE NOT completed AND assigned_tech = ?
                   ) THEN 'Task'
               END
           ) AS alert_levels,
           (SELECT creator FROM help.notes WHERE linked_id = tickets.id ORDER BY 
               (CASE WHEN date_override IS NULL THEN created ELSE date_override END) DESC LIMIT 1) AS latest_note_author
    FROM tickets
    LEFT JOIN alerts ON tickets.id = alerts.ticket_id
    WHERE tickets.status NOT IN ('Closed', 'Resolved')
      AND (tickets.employee = ? 
           OR tickets.id IN (
               SELECT ticket_id 
               FROM ticket_tasks 
               WHERE NOT completed AND assigned_tech = ?
           ))
    GROUP BY tickets.id
    ORDER BY tickets.id ASC
    STR;

$tech_ticket_result = HelpDB::get()->execute_query($tech_ticket_query, [$username, $username, $username]);
$my_tickets = get_parsed_ticket_data($tech_ticket_result);


$client_ticket_query = <<<STR
    SELECT *
    FROM tickets
    WHERE status NOT IN ('Closed', 'Resolved')
    AND (client = ?
        OR cc_emails REGEXP CONCAT('(^|,)', ?, '@')
        OR bcc_emails REGEXP CONCAT('(^|,)', ?, '@'))
    AND (employee != ? OR employee IS NULL)
    ORDER BY id ASC
STR;

$client_ticket_result = HelpDB::get()->execute_query($client_ticket_query, [$username, $username, $username, $username]);
$client_tickets = get_parsed_ticket_data($client_ticket_result);

$alert_result = HelpDB::get()->execute_query("SELECT * FROM alerts WHERE employee = ? AND supervisor_alert = 0", [$username]);
$alerts = get_parsed_alert_data($alert_result);
echo $twig->render('tickets.twig', [
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
    'my_tickets' => $my_tickets,
    'open_tickets' => $client_tickets,
    'alerts' => $alerts,
    'hide_alerts' => $_SESSION['hide_alerts'],
    'show_alerts' => $show_alerts,
    'num_project_tickets' => $num_project_tickets
    // 'show_alerts' => 1
]);
