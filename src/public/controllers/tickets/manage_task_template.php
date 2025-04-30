<?php
require_once('init.php');
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

$user_id = get_id_for_user($_SESSION['username']);
// $username = $_SESSION['username'];

// Fetch task templates created by the logged-in user
$templates_query = "SELECT * FROM task_templates WHERE created_by = ? ORDER BY template_group ASC";
$templates_result = HelpDB::get()->execute_query($templates_query, [$user_id]);
$templates = $templates_result->fetch_all(MYSQLI_ASSOC);

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

//Handle Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $description = htmlspecialchars(trim($_POST['description']), ENT_QUOTES, 'UTF-8');
    $required = isset($_POST['required']) ? 1 : 0;
    $assigned_tech = isset($_POST['assigned_tech']) ? htmlspecialchars(trim($_POST['assigned_tech']), ENT_QUOTES, 'UTF-8') : null;
    $template_group = isset($_POST['template_group']) ? htmlspecialchars(trim($_POST['template_group']), ENT_QUOTES, 'UTF-8') : null;
    $created_by = get_id_for_user($_SESSION['username']); // Get the logged-in user's ID

    // Insert the new task template
    $insert_query = "INSERT INTO task_templates (description, required,assigned_tech, created_by, template_group) VALUES (?, ?, ?, ?, ?)";
    try {
        $insert_result = HelpDB::get()->execute_query($insert_query, [$description, $required, $assigned_tech, $created_by, $template_group]);
        header('Location: manage_task_template.php');
        exit;
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) { // Duplicate entry error
            $error_message = "A task template with this name already exists.";
        } else {
            $error_message = "Failed to create task template: " . $e->getMessage();
        }
    }
}

// Render the Twig template
$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);

echo $twig->render('manage_task_template.twig', [
    // base variables
    'color_scheme' => $color_scheme,

    // task template variables
    'templates' => $templates,
    'tech_usernames_parsed' => $tech_usernames_parsed,
    'tech_usernames_map' => $tech_usernames_map,
    'groups' => $groups,
    'error_message' => $error_message ?? null
]);
