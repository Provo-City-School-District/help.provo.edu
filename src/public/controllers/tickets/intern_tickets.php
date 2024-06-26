<?php
require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/ticket_base_variables.php");
require "ticket_utils.php";

session_start();

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache')
]);

$ticket_query = <<<QUERY
    SELECT * FROM tickets WHERE intern_visible = 1 AND location = ?;
QUERY;

$intern_site = $_SESSION["permissions"]["intern_site"];
if (!isset($intern_site) || $intern_site == 0) {
    log_app(LOG_INFO, "[intern_tickets.php] intern_site not set. Exiting...");
    die;
}

$site_name = location_name_from_id($intern_site);
$ticket_result = HelpDB::get()->execute_query($ticket_query, [$intern_site]);
$tickets = get_parsed_ticket_data($ticket_result);

echo $twig->render('intern_tickets.twig', [
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

    // ticket_table_base variables
    'tickets' => $tickets,

    // intern_tickets variables
    'site_name' => $site_name
]);
