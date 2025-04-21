<?php
require "helpdbconnect.php";
require "ticket_utils.php";
require "api_authenticate.php";

// make sure ticket title and ticket description are set
if (!isset($_POST["work_order"]) || 
    !isset($_POST["content"])) {
    http_response_code(400);
    exit;
}

create_note($_POST["work_order"],
    'System',
    $_POST["content"],
    0,
    0,
    0,
    0,
    true);

$data = [];
$data["success"] = true;

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);