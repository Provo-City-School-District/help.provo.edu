<?php
require("helpdbconnect.php");
require("block_file.php");
require("functions.php");
require("ticket_utils.php");

$get_filtered = filter_input_array(INPUT_GET, [
    "template_name" => FILTER_SANITIZE_STRING
]);

$username = $_SESSION["username"];
$result = HelpDB::get()->execute_query(
    "SELECT content FROM note_templates WHERE (name = ? AND user_id = ?)",
    [$get_filtered["template_name"], get_id_for_user($username)]);

if (!$result) {
   http_response_code(500);
   die;
}

$data = [
    "content" => $result->fetch_assoc()["content"]
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);