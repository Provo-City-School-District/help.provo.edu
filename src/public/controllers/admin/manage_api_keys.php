<?php

require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');
require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/base_variables.php");

if ($_SESSION['permissions']['is_admin'] != 1) {
    echo 'You do not have permission to use this form.';
    exit;
}

function generate_api_key() {
    return bin2hex(random_bytes(32));
}

// Handle form submissions
$db = HelpDB::get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $name = trim($_POST['name']);
        $api_key = generate_api_key();
        $hashed_key = hash('sha256', $api_key);
        $db->execute_query("INSERT INTO api_keys (name, api_key) VALUES (?, ?)", [$name, $hashed_key]);
        $_SESSION['new_key'] = $api_key; // show plain text once
    }

    if (isset($_POST['delete'])) {
        $name = trim($_POST['delete']);
        $db->execute_query("DELETE FROM api_keys WHERE name = ?", [$name]);
    }

    if (isset($_POST['regenerate'])) {
        $name = trim($_POST['regenerate']);
        $api_key = generate_api_key();
        $hashed_key = hash('sha256', $api_key);
        $db->execute_query("UPDATE api_keys SET api_key = ? WHERE name = ?", [$hashed_key, $name]);
        $_SESSION['new_key'] = $api_key;
    }

    header("Location: manage_api_keys.php");
    exit;
}

// Load keys
$api_keys = $db->execute_query("SELECT name FROM api_keys");

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);

echo $twig->render('manage_api_keys.twig', [
    'color_scheme' => $color_scheme,
    'current_year' => $current_year,
    'user_permissions' => $permissions,
    'wo_time' => $wo_time,
    'user_pref' => $user_pref,
    'ticket_limit' => $ticket_limit,
    'status_alert_type' => $status_alert_type,
    'status_alert_message' => $status_alert_message,
    'app_version' => $app_version,
    'api_keys' => $api_keys,
    'new_key' => $_SESSION['new_key'] ?? null
]);

unset($_SESSION['new_key']); // only show once
