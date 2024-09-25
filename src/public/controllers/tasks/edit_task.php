<?php
require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/ticket_base_variables.php");
require_once "ticket_utils.php";

session_start();

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache')
]);

$task_id = filter_input(INPUT_GET, 'task_id', FILTER_VALIDATE_INT);
$task_info_query = <<<STR
    SELECT * FROM ticket_tasks WHERE id = ?
STR;

$task_info_result = HelpDB::get()->execute_query($task_info_query, [$task_id]);
$task = $task_info_result->fetch_assoc();
if (!$task) {
    echo "Task doesn't exist!";
    die;
}


$tech_usernames = get_tech_usernames();
$tech_usernames_parsed = [];
foreach ($tech_usernames as $username) {
    $name = get_local_name_for_user($username);
    $firstname = ucwords(strtolower($name["firstname"]));
    $lastname = ucwords(strtolower($name["lastname"]));
    $display_string = $firstname . " " . $lastname . " - " . location_name_from_id(get_fast_client_location($username) ?: "");
    $tech_usernames_parsed[] = [
        $username,
        $display_string
    ];
}

echo $twig->render('edit_task.twig', [
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

    // edit_task variables
    'task' => $task,
    'tech_usernames' => $tech_usernames_parsed
]);
