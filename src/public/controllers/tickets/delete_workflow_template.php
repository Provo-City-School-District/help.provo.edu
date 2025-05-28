<?php
require "block_file.php";
require_once("helpdbconnect.php");

session_start();
$current_user_id = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step_id']) && $current_user_id) {
    $template_id = intval($_POST['step_id']);

    // Check if the template belongs to the current user
    $result = HelpDB::get()->execute_query(
        "SELECT id FROM workflow_templates WHERE id = ? AND created_by = ?",
        [$template_id, $current_user_id]
    );
    if ($result && $result->num_rows > 0) {
        HelpDB::get()->execute_query("DELETE FROM workflow_templates WHERE id = ?", [$template_id]);
        log_app(LOG_INFO, "[delete_workflow_template.php] Deleted workflow template with ID: $template_id by user $current_user_id");
        header('Location: manage_workflow_template.php');
        exit;
    } else {
        http_response_code(403);
        echo "You do not have permission to delete this workflow template.";
        exit;
    }
} else {
    http_response_code(400);
    echo "Invalid request.";
}
