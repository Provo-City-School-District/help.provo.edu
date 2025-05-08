<?php
require_once("helpdbconnect.php");
require_once("block_file.php");
require_once("functions.php");
require_once("ticket_utils.php");

$post_filtered = filter_input_array(INPUT_POST, [
    "attachment_path" => FILTER_SANITIZE_STRING,
    "ticket_id" => FILTER_VALIDATE_INT
]);

$attachment_path = $post_filtered["attachment_path"];
$ticket_id = $post_filtered["ticket_id"];

$username = $_SESSION["username"];

if (!user_is_tech($username)) {
    log_app(LOG_INFO, "[delete_attachment.php] User is not a tech. Ignoring ajax request...");
    http_response_code(401);
    exit;
}

log_app(LOG_INFO, "[delete_attachment.php] Deleting attachment $attachment_path on ticket=$ticket_id");

$fetch_attachment_res = HelpDB::get()->execute_query("SELECT attachment_path FROM help.tickets WHERE id = ?", [$ticket_id]);

$old_attachment_path = $fetch_attachment_res->fetch_assoc()["attachment_path"];
$split_old_attachment_path = explode(',', $old_attachment_path);
$split_new_attachment_path = array_diff($split_old_attachment_path, [$attachment_path]);
$new_attachment_path = implode(',', $split_new_attachment_path);

$update_attachment_res = HelpDB::get()->execute_query("UPDATE help.tickets SET attachment_path = ? WHERE id = ?", [$new_attachment_path, $ticket_id]);

$real_filename = basename($attachment_path);
$real_user_path = realpath(from_root("/../uploads/$real_filename"));
$real_base_path = realpath(from_root("/../uploads/")) . DIRECTORY_SEPARATOR;

// Validate that the file is being accessed in ${PROJECT_ROOT}/uploads
if ($real_user_path === false || (substr($real_user_path, 0, strlen($real_base_path)) != $real_base_path)) {
    log_app(LOG_ERR, "[delete_attachment.php] Attemped to delete a file that was not in /uploads/");
    http_response_code(401);
    exit;
}

$file_deleted = false;
if (file_exists($real_user_path)) {
    $file_deleted = unlink($real_user_path);
}

if (!$update_attachment_res || !$file_deleted) {
    log_app(LOG_ERR, "[delete_attachment.php] Failed to execute query");
    http_response_code(500);
} else {
    log_app(LOG_INFO, "[delete_attachment.php] Successfully deleted attachment $attachment_path on ticket=$ticket_id");
    http_response_code(200);
}
