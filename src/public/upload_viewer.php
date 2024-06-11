<?php
require "block_file.php";
require "file_upload_utils.php";

$file_path = urldecode(basename($_GET["file"]));

$real_user_path = realpath(from_root("/../uploads/$file_path"));
$real_base_path = realpath(from_root("/../uploads/")).DIRECTORY_SEPARATOR;


// Validate that the file is being accessed in ${PROJECT_ROOT}/uploads
if ($real_user_path === false || (substr($real_user_path, 0, strlen($real_base_path)) != $real_base_path)) {
    echo "Error parsing file request";
    exit;
}

$content_type = mime_content_type($real_user_path);
if (!$content_type) {
    echo "Failed to get file type";
    exit;
}

$allowed_mime_types = get_allowed_mime_types();

if (!in_array($content_type, $allowed_mime_types)) {
    echo "Error parsing file request (invalid MIME type)";
    exit;
}

$content_size = filesize($real_user_path);

// Some files can be previewed directly by browser, conditionally set the HTTP header and download attachment if not
$embeddable_types = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'application/pdf', 'text/plain', 'audio/mpeg', 'video/mp4'];

if (!in_array($content_type, $embeddable_types)) {
    header("Content-Disposition: attachment; filename=\"$file_path\"");
}

header("Content-Type: $content_type");
header("Content-Length: $content_size");
readfile("$real_user_path");
exit;
