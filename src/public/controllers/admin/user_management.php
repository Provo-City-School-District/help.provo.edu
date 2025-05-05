<?php
require_once from_root('/../vendor/autoload.php');
require_once from_root("/new-controllers/base_variables.php");
require "ticket_utils.php";

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);

if ($_SESSION['permissions']['is_admin'] != 1) {
    // User is not an admin
    echo 'You do not have permission to view this page.';
    exit;
}

$is_developer = get_user_setting(get_id_for_user($_SESSION['username']), "is_developer") ?? 0;

// Execute the SELECT query to retrieve all users and their permissions/settings
$user_query = <<<SQL
SELECT u.*, us.*
FROM users u
LEFT JOIN user_settings us ON u.id = us.user_id
ORDER BY u.username ASC
SQL;

$user_result = HelpDB::get()->execute_query($user_query);

// Check if the query was successful
if (!$user_result) {
    die("Query failed: " . mysqli_error(HelpDB::get()));
}

// Fetch all rows as an associative array
$users = [];
while ($row = $user_result->fetch_assoc()) {
    $row['department'] = get_user_department_name($row['department']) ?? 'N/A';
    $users[] = $row;
}


echo $twig->render('user_management.twig', [
    // base variables
    'color_scheme' => $color_scheme,
    'current_year' => $current_year,
    'user_permissions' => $permissions,
    'wo_time' => $wo_time,
    'user_pref' => $user_pref,
    'ticket_limit' => $ticket_limit,
    'app_version' => $app_version,

    // Page Variables
    'user_result' => $users,
    'is_developer' => $is_developer,
]);
