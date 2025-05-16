<?php
require_once from_root('/../vendor/autoload.php');
require_once from_root("/new-controllers/base_variables.php");
require_once "ticket_utils.php";


// Check if user is Admin or Supervisor
if (!get_user_setting(get_id_for_user($_SESSION['username']), "is_admin") || !get_user_setting(get_id_for_user($_SESSION['username']), "is_supervisor"))
    if ($is_admin != 1) {
        // User is not an admin
        echo 'You do not have permission to view this page.';
        exit;
    }


$step_id = intval($_GET['step_id'] ?? 0);
if ($step_id <= 0) {
    die("Invalid step ID.");
}

// Fetch the workflow step
$step_res = HelpDB::get()->execute_query(
    "SELECT * FROM ticket_workflow_steps WHERE id = ?",
    [$step_id]
);
$step = $step_res ? $step_res->fetch_assoc() : null;
if (!$step) {
    die("Workflow step not found.");
}

// Fetch tech usernames for the dropdown
$user_department = $_SESSION['department'] ?? null;
$can_see_all_techs = $_SESSION['permissions']['can_see_all_techs'] ?? 0;
if ($can_see_all_techs) {
    $tech_usernames_res = HelpDB::get()->execute_query(
        "SELECT u.username FROM users u LEFT JOIN user_settings us ON u.id = us.user_id WHERE us.is_tech = 1 ORDER BY u.username ASC"
    );
} else {
    $tech_usernames_res = HelpDB::get()->execute_query(
        "SELECT u.username FROM users u LEFT JOIN user_settings us ON u.id = us.user_id WHERE us.is_tech = 1 AND us.department = ? ORDER BY u.username ASC",
        [$user_department]
    );
}
$tech_usernames = [];
while ($row = $tech_usernames_res->fetch_assoc()) {
    $tech_usernames[] = $row['username'];
}


// initialize Twig
$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);

// Render Twig template
echo $twig->render('workflow_edit.twig', [
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
    'num_assigned_tasks' => $num_assigned_tasks,
    'num_subordinate_tickets' => $num_subordinate_tickets,

    'page_title' => 'Edit Workflow Step',
    'step' => $step,
    'tech_usernames' => $tech_usernames
]);
