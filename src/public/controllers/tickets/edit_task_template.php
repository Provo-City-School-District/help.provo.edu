<?php
require_once('init.php');
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

$template_user = filter_var($_GET['created_by'], FILTER_SANITIZE_NUMBER_INT);
$template_description = htmlspecialchars($_GET['description'], ENT_QUOTES, 'UTF-8');
$template_group = htmlspecialchars($_GET['template_group'], ENT_QUOTES, 'UTF-8');

$user_id = get_id_for_user($_SESSION['username']);

// Fetch the task template to ensure it belongs to the user
$template_query = "SELECT * FROM task_templates WHERE description = ? AND created_by = ? AND template_group = ?";
$template_result = HelpDB::get()->execute_query($template_query, [$template_description, $template_user, $template_group]);
$template = $template_result->fetch_assoc();
if (!$template) {
    echo "Task template not found or not owned by you.";
    exit;
}


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
// Create a map for tech usernames
$tech_usernames_map = [];
foreach ($tech_usernames_parsed as $tech) {
    $tech_usernames_map[$tech['username']] = $tech['display_string'];
}


// get user settings
$color_scheme = get_user_setting($user_id, 'color_scheme');


// Fetch available groups
$groups_query = "SELECT DISTINCT template_group FROM task_templates WHERE template_group IS NOT NULL";
$groups_result = HelpDB::get()->execute_query($groups_query);
$groups = [];
while ($row = $groups_result->fetch_assoc()) {
    $groups[] = $row;
}



// Handle Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['template_group']), ENT_QUOTES, 'UTF-8');
    $org_template_name = htmlspecialchars(trim($_POST['org_template_name']), ENT_QUOTES, 'UTF-8');
    $created_by = htmlspecialchars(trim($_POST['created_by']), ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(trim($_POST['description']), ENT_QUOTES, 'UTF-8');
    $assigned_tech = isset($_POST['assigned_tech']) ? htmlspecialchars(trim($_POST['assigned_tech']), ENT_QUOTES, 'UTF-8') : null;
    $required = isset($_POST['required']) ? 1 : 0;

    $check_query = "SELECT 1 FROM task_templates WHERE created_by = ? AND template_group = ? AND description = ? AND NOT (template_group = ? AND description = ?)";
    $check_result = HelpDB::get()->execute_query($check_query, [$created_by, $name, $description, $org_template_name, $template['description']]);

    if ($check_result->num_rows > 0) {
        $error_message = "A task template with the same group and description already exists.";
    } else {
        // Proceed with the update
        $update_query = "UPDATE task_templates SET template_group = ?, description = ?, required = ?, assigned_tech = ? WHERE template_group = ? AND created_by = ? AND description = ?";
        $update_result = HelpDB::get()->execute_query($update_query, [$name, $description, $required, $assigned_tech, $org_template_name, $created_by, $template['description']]);

        if ($update_result) {
            header('Location: manage_task_template.php');
            exit;
        } else {
            $error_message = "Failed to update task template.";
        }
    }
}



// Render the Twig template
$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);
echo $twig->render('edit_task_template.twig', [
    // base variables
    'color_scheme' => $color_scheme,

    // task template variables
    'template' => $template,
    'tech_usernames_parsed' => $tech_usernames_parsed,
    'tech_usernames_map' => $tech_usernames_map,
    'groups' => $groups,
    'error_message' => $error_message ?? null
]);
