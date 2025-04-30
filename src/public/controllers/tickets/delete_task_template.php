<?php
require_once('init.php');
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

// Get the template ID and the logged-in user's ID
$user_id = filter_var($_GET['created_by'], FILTER_SANITIZE_NUMBER_INT);
$template_name = rawurldecode($_GET['template_group']);
$template_description = htmlspecialchars($_GET['description'], ENT_QUOTES, 'UTF-8');


// var_dump($template_name);
// echo "<br>";
// var_dump($user_id);

if (!$template_name) {
    echo "Invalid template name";
    exit;
}
if (!$user_id) {
    echo "User not logged in.";
    exit;
}

// Verify that the task template belongs to the logged-in user
$template_query = "SELECT * FROM task_templates WHERE template_group = ? AND created_by = ? AND description = ?";
$template_result = HelpDB::get()->execute_query($template_query, [$template_name, $user_id, $template_description]);
$template = $template_result->fetch_assoc();

if (!$template) {
    echo "Task template not found or not owned by you.";
    exit;
}

// Delete the task template
$delete_query = "DELETE FROM task_templates WHERE template_group = ? AND created_by = ? AND description = ?";
$delete_result = HelpDB::get()->execute_query($delete_query, [$template_name, $user_id, $template_description]);

if ($delete_result) {
    header('Location: manage_task_template.php');
    exit;
} else {
    echo "Failed to delete task template.";
}
