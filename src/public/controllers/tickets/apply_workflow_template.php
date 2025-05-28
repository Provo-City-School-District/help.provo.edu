<?php
require_once('helpdbconnect.php');
require_once('block_file.php');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$ticket_id = intval($data['ticket_id'] ?? 0);
$workflow_group = trim($data['workflow_group'] ?? '');

if (!$ticket_id || !$workflow_group) {
    echo json_encode(['success' => false, 'message' => 'Missing ticket ID or workflow group.']);
    exit;
}

// Fetch workflow template steps for the group
$steps_res = HelpDB::get()->execute_query(
    "SELECT * FROM workflow_templates WHERE workflow_group = ? ORDER BY step_order ASC",
    [$workflow_group]
);

if (!$steps_res || $steps_res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'No workflow steps found for this group.']);
    exit;
}

// Insert each step into ticket_workflow_steps
while ($step = $steps_res->fetch_assoc()) {
    HelpDB::get()->execute_query(
        "INSERT INTO ticket_workflow_steps (ticket_id, step_order, step_name, assigned_user, status)
         VALUES (?, ?, ?, ?, 'pending')",
        [$ticket_id, $step['step_order'], $step['step_name'], $step['assigned_user']]
    );
}

echo json_encode(['success' => true]);
