<?php
require_once from_root('/vendor/autoload.php');
require_once from_root("/new-controllers/ticket_base_variables.php");
require_once "helpdbconnect.php";

$loader = new \Twig\Loader\FilesystemLoader(from_root('/views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);


echo $twig->render('note_shortcuts.twig', [
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
]);
