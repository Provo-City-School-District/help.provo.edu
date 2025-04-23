<?php
require 'block_file.php';
require 'ticket_utils.php';
require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/base_variables.php");

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
	'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);


$user_id = get_id_for_user($_SESSION["username"]);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name'], $_POST['content'])) {
        // Add or update template
        $stmt = HelpDB::get()->execute_query(
            "INSERT INTO note_templates (user_id, name, content)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE content = VALUES(content)",
            [$user_id, $_POST['name'], $_POST['content']]
        );
        header("Location: manage_note_templates.php");
        exit;
    } elseif (isset($_POST['name']) && !isset($_POST['content'])) {
        // Delete template
        $stmt = HelpDB::get()->execute_query(
            "DELETE FROM note_templates WHERE user_id = ? AND name = ?",
            [$user_id, $_POST['name']]
        );
        header("Location: manage_note_templates.php");
        exit;
    }
}

$templates_result = HelpDB::get()->execute_query("SELECT * FROM note_templates WHERE user_id = ?", [$user_id]);


$templates = $templates_result->fetch_all(MYSQLI_ASSOC);

echo $twig->render('manage_note_templates.twig', [
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

    // ticket_table_base variables
    'templates' => $templates
]);
