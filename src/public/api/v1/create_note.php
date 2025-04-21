<?php
require "helpdbconnect.php";
require "ticket_utils.php";

$headers = apache_request_headers();

// check that api key is included
if (isset($headers['Authorization'])) {
    $api_key = $headers['Authorization'];
} else {
    http_response_code(401);
    exit;
}

// check that api key is actually valid
$hashed_key = hash('sha256', $api_key);
$result = HelpDB::get()->execute_query("SELECT 1 FROM api_keys WHERE api_key = ? LIMIT 1", [$hashed_key]);

if ($result && $result->num_rows > 0) {
    // api key exists, continue

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
} else {
    http_response_code(401);
    exit;
}