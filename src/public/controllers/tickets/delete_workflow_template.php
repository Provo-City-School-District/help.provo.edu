<?php
require "block_file.php";
require_once("helpdbconnect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step_id'])) {
    $template_id = intval($_POST['step_id']);

    $delete_query = HelpDB::get()->execute_query("DELETE FROM workflow_templates WHERE id = ?", [$template_id]);

    log_app(LOG_INFO, "[delete_workflow_template.php] Deleted workflow template with ID: $template_id");

    header('Location: manage_workflow_template.php');
    exit;
} else {
    http_response_code(400);
    echo "Invalid request.";
}
