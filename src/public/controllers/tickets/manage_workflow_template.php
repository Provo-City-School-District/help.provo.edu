<?php
require_once('init.php');
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

$user_id = get_id_for_user($_SESSION['username']);
$username = $_SESSION['username'];

// Get Tech Usernames
$tech_usernames = get_tech_usernames();
$tech_usernames_parsed = [];
foreach ($tech_usernames as $username) {
    $name = get_local_name_for_user($username);
    $firstname = ucwords(strtolower($name["firstname"]));
    $lastname = ucwords(strtolower($name["lastname"]));
    $display_string = $firstname . " " . $lastname . " - " . location_name_from_id(get_fast_client_location($username) ?: "");
    $tech_usernames_parsed[] = [
        'username' => $username,
        'display_string' => $display_string
    ];
}

// build tech users map
$tech_usernames_map = [];
foreach ($tech_usernames_parsed as $tech) {
    $tech_usernames_map[$tech['username']] = $tech['display_string'];
}

// get user settings
$color_scheme = get_user_setting($user_id, 'color_scheme');





// Fetch workflow templates created by the logged-in user
$templates_query = "SELECT * FROM workflow_templates WHERE created_by = ? ORDER BY workflow_group ASC, step_order ASC";
$templates_result = HelpDB::get()->execute_query($templates_query, [$user_id]);
$templates = $templates_result->fetch_all(MYSQLI_ASSOC);

// Fetch available workflow groups for the datalist
$groups_query = "SELECT DISTINCT workflow_group FROM workflow_templates WHERE workflow_group IS NOT NULL";
$groups_result = HelpDB::get()->execute_query($groups_query);
$groups = [];
while ($row = $groups_result->fetch_assoc()) {
    $groups[] = $row;
}

//Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workflow_group = htmlspecialchars(trim($_POST['workflow_group']), ENT_QUOTES, 'UTF-8');
    $step_order = intval($_POST['step_order']);
    $step_name = htmlspecialchars(trim($_POST['step_name']), ENT_QUOTES, 'UTF-8');
    $assigned_user = isset($_POST['assigned_user']) ? htmlspecialchars(trim($_POST['assigned_user']), ENT_QUOTES, 'UTF-8') : null;
    $created_by = $user_id;

    $insert_query = "INSERT INTO workflow_templates (workflow_group, step_order, step_name, assigned_user, created_by) VALUES (?, ?, ?, ?, ?)";
    try {
        HelpDB::get()->execute_query($insert_query, [$workflow_group, $step_order, $step_name, $assigned_user, $created_by]);
        header('Location: manage_workflow_template.php');
        exit;
    } catch (mysqli_sql_exception $e) {
        $error_message = "Failed to create workflow template: " . $e->getMessage();
    }
}

// Render the Twig template
$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);

echo $twig->render('manage_workflow_template.twig', [
    // base variables
    'color_scheme' => $color_scheme,

    // Workflow template variables
    'tech_usernames_parsed' => $tech_usernames_parsed,
    'tech_usernames_map' => $tech_usernames_map,
    'error_message' => $error_message ?? null,
    'templates' => $templates,
    'groups' => $groups,
]);
