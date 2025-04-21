<?php
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

if (!($result && $result->num_rows > 0)) {
    http_response_code(401);
    exit;
}