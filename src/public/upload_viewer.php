<?php
require "block_file.php";

$file_path = basename($_GET["file"]);

$real_user_path = realpath(from_root("/../uploads/$file_path"));
$real_base_path = realpath(from_root("/../uploads/"));


// Validate that the file is being accessed in ${PROJECT_ROOT}/uploads
if ($real_user_path === false || (substr($real_user_path, 0, strlen($real_base_path)) != $real_base_path)) {
	echo "Error parsing file request";
	exit;
}

$content_type = mime_content_type($real_user_path);
if (!$content_type) {
	echo "Failed to get file type";
}

$content_size = filesize($real_user_path);

header("Content-Type: $content_type");
header("Content-Length: $content_size");
readfile("$real_user_path");
exit;