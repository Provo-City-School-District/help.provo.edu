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
    if (!isset($_POST["ticket_title"]) || !isset($_POST["ticket_description"])) {
        http_response_code(400);
        exit;
    }

    $created_ticket_id = 0;
    create_ticket("donotreply", $_POST["ticket_title"], $_POST["ticket_description"], "", 0, $created_ticket_id);

    $data = [];
    $data["ticket_id"] = $created_ticket_id;
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
} else {
    http_response_code(401);
    exit;
}