<?php
require "api_authenticate.php";
require "ticket_utils.php";

// make sure ticket title and ticket description are set
if (
    !isset($_POST["ticket_title"]) ||
    !isset($_POST["ticket_description"]) ||
    !isset($_POST["ticket_department"]) ||
    !isset($_POST["ticket_location"])
) {
    http_response_code(400);
    exit;
}
// Check for client, default to "donotreply" if not provided
$client = isset($_POST["ticket_client"]) ? $_POST["ticket_client"] : "donotreply";

$params = [
    'client' => $client,
    'title' => $_POST["ticket_title"],
    'desc' => $_POST["ticket_description"],
    'location' => $_POST["ticket_location"],
    'department' => $_POST["ticket_department"]
];
$created_ticket_id = __create_ticket($params);

$data = [];
$data["ticket_id"] = $created_ticket_id;

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
