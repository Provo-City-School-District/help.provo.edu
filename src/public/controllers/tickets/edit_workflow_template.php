<?php
require_once('init.php');
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

session_start();
$user_id = get_id_for_user($_SESSION['username']);
$username = $_SESSION['username'];
$color_scheme = get_user_setting($user_id, 'color_scheme');
$error_message = null;

// Fetch tech usernames for dropdown
$tech_usernames = get_tech_usernames();
$tech_usernames_parsed = [];
foreach ($tech_usernames as $tech_username) {
    $name = get_local_name_for_user($tech_username);
    $firstname = ucwords(strtolower($name["firstname"]));
    $lastname = ucwords(strtolower($name["lastname"]));
    $display_string = $firstname . " " . $lastname . " - " . location_name_from_id(get_fast_client_location($tech_username) ?: "");
    $tech_usernames_parsed[] = [
        'username' => $tech_username,
        'display_string' => $display_string
    ];
}

// Get step_id from POST
$step_id = isset($_POST['step_id']) ? intval($_POST['step_id']) : null;
if (!$step_id) {
    http_response_code(400);
    echo "Invalid workflow template ID.";
    exit;
}

// Fetch the workflow template, ensure it belongs to the user
$template_query = "SELECT * FROM workflow_templates WHERE id = ? AND created_by = ?";
$template_result = HelpDB::get()->execute_query($template_query, [$step_id, $user_id]);
$template = $template_result->fetch_assoc();

if (!$template) {
    http_response_code(403);
    echo "You do not have permission to edit this workflow template.";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_workflow_template') {
    $workflow_group = htmlspecialchars(trim($_POST['workflow_group']), ENT_QUOTES, 'UTF-8');
    $step_order = intval($_POST['step_order']);
    $step_name = htmlspecialchars(trim($_POST['step_name']), ENT_QUOTES, 'UTF-8');
    $assigned_user = isset($_POST['assigned_user']) ? htmlspecialchars(trim($_POST['assigned_user']), ENT_QUOTES, 'UTF-8') : null;

    $update_query = "UPDATE workflow_templates SET workflow_group = ?, step_order = ?, step_name = ?, assigned_user = ? WHERE id = ? AND created_by = ?";
    try {
        HelpDB::get()->execute_query($update_query, [$workflow_group, $step_order, $step_name, $assigned_user, $step_id, $user_id]);
        header('Location: manage_workflow_template.php');
        exit;
    } catch (mysqli_sql_exception $e) {
        $error_message = "Failed to update workflow template: " . $e->getMessage();
    }
}

// Fetch available workflow groups for the datalist
$groups_query = "SELECT DISTINCT workflow_group FROM workflow_templates WHERE workflow_group IS NOT NULL";
$groups_result = HelpDB::get()->execute_query($groups_query);
$groups = [];
while ($row = $groups_result->fetch_assoc()) {
    $groups[] = $row;
}

// Render the Twig template
$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);

echo $twig->render('edit_workflow_template.twig', [
    // base variables
    'color_scheme' => $color_scheme,

    'template' => $template,
    'tech_usernames_parsed' => $tech_usernames_parsed,
    'groups' => $groups,
    'error_message' => $error_message,
]);
