<?php
require_once from_root('/../vendor/autoload.php');
require_once from_root("/new-controllers/base_variables.php");
require "ticket_utils.php";

// Check if user is Admin
$is_admin = get_user_setting(get_id_for_user($_SESSION['username']), "is_admin") ?? 0;
$is_developer = get_user_setting(get_id_for_user($_SESSION['username']), "is_developer") ?? 0;

if ($is_admin != 1) {
    // User is not an admin
    echo 'You do not have permission to view this page.';
    exit;
}



$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);




// fetch ticket feedback   
$feedback_query = <<<STR
SELECT * FROM feedback
ORDER BY id DESC
STR;
$feedback_result = HelpDB::get()->execute_query($feedback_query);

// parse feedback results
$feedback_parsed = [];
while ($row = mysqli_fetch_assoc($feedback_result)) {
    $feedback_parsed[] = [
        'ticket_id' => $row['ticket_id'],
        'client' => $row['client'],
        'rating' => $row['rating'],
        'comments' => $row['comments'],
        'created_at' => date('Y-m-d H:i:s', strtotime($row['created_at'])),
    ];
}

echo $twig->render('view_feedback.twig', [
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

    // Page Variables
    'feedback_parsed' => $feedback_parsed,
    'is_developer' => $is_developer,
]);
