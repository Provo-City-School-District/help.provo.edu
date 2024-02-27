<?php
require_once("block_file.php");
// Start the session
require_once('init.php');
require_once('helpdbconnect.php');
// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // User is not logged in, redirect to login page
    header('Location: /index.php');
    exit;
}

// Check if the ticket ID is set
if (!isset($_POST['ticket_id'])) {
    // Ticket ID is not set, redirect to tickets page
    header('Location: tickets.php');
    exit;
}

function KiB(int $bytes)
{
    return $bytes * 1024;
}

function MiB(int $bytes)
{
    return KiB($bytes) * 1024;
}

function compress_and_resize_image(string $image_path, string $image_type)
{
    $newWidth = 1500;

    $image = null;
    if ($image_type == "image/jpeg")
        $image = imagecreatefromjpeg($image_path);
    else if ($image_type == "image/png")
        $image = imagecreatefrompng($image_path);
    else
        return false;


    $size = getimagesize($image_path);
    $oldWidth = $size[0];
    $oldHeight = $size[1];

    $image_to_compress = $image;
    if ($oldWidth > $newWidth) {
        $width_change_ratio = $newWidth / $oldWidth;
        $newHeight = $size[1] * $width_change_ratio;

        $image_to_compress = imagescale($image, $newWidth, $newHeight);
    }

    // Already validated it's jpg or png above
    if ($image_type == "image/jpeg")
        return imagejpeg($image_to_compress, $image_path, 90);
    else
        // Uses default zlib compression
        return imagepng($image_to_compress, $image_path);
}

// Get the ticket ID and username from the POST data
$ticket_id = $_POST['ticket_id'];
$username = $_POST['username'];

// Define the allowed file extensions
$allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip', 'rar', '7z', 'tar', 'mp3', 'mp4', 'svg'];
/*$allowed_mime_types = [
    'txt' => 'text/plain',
    'png' => 'image/png',
    'jpeg' => 'image/jpeg',
    'jpg' => 'image/jpeg',
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'docx' => 'application/msword',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'csv' => 'text/csv',
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    '7z' => 'application/x-7z-compressed',
    'tar' => 'application/x-tar',
    'mp3' => 'audio/mpeg',
    'mp4' => 'video/mp4',
    'svg' => 'image/svg+xml',
];*/

// Define max filesize
// TODO: make 100, however POST length needs to be increased
$maxFileSize = MiB(50);

// Array of arrays 
$failed_files = [];

// Check if any files were uploaded
if (isset($_FILES['attachment'])) {
    // Loop through the uploaded files
    for ($i = 0; $i < count($_FILES['attachment']['name']); $i++) {
        // Get the file name and temporary file path
        $fileName = $_FILES['attachment']['name'][$i];
        $tmpFilePath = $_FILES['attachment']['tmp_name'][$i];
        if ($tmpFilePath == null || $fileName == null) {
            $failed_files[] = [
                "filename" => null,
                "fail_reason" => "File is null"
            ];
            continue;
        }

        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fileType = mime_content_type($tmpFilePath);

        // Filesize in bytes
        $fileSize = filesize($tmpFilePath);
        // Check if the file was uploaded successfully and has an allowed extension / file type
        // Will also check the file to validate the file is what it claims (eg: cant rename .exe to .png)
        if ($tmpFilePath != "") {
            // Max upload size
            if ($fileSize <= $maxFileSize) {
                if (
                    in_array($fileExtension, $allowed_extensions)
                ) {
                    // Generate a unique file name
                    $newFilePath = "/uploads/" . $ticket_id . "-" . $fileName;
                    $absolute_path = from_root($newFilePath);

                    if ($fileType == "image/png" || $fileType == "image/jpeg")
                        compress_and_resize_image($tmpFilePath, $fileType);

                    // Move the file to the uploads directory
                    if (move_uploaded_file($tmpFilePath, $absolute_path)) {
                        // File was uploaded successfully, insert the file path into the database

                        $query = "UPDATE tickets SET attachment_path = 
                        (CASE WHEN attachment_path IS null OR attachment_path = '' THEN ? ELSE CONCAT(attachment_path, ',', ?) END)
                        WHERE id = ?";
                        $stmt = mysqli_prepare($database, $query);
                        mysqli_stmt_bind_param($stmt, "ssi", $newFilePath, $newFilePath, $ticket_id);
                        mysqli_stmt_execute($stmt);
                    } else {
                        $failed_files[] = [
                            "filename" => $fileName,
                            "fail_reason" => "Failed to move file to the uploads directory"
                        ];
                    }
                } else {
                    $failed_files[] = [
                        "filename" => $fileName,
                        "fail_reason" => "'$fileExtension' or '$fileType' not allowed"
                    ];
                }
            } else {
                $failed_files[] = [
                    "filename" => $fileName,
                    "fail_reason" => "File size is too large ($fileSize MiB > $maxFileSize MiB)"
                ];
            }
        } else {
            $failed_files[] = [
                "filename" => $fileName,
                "fail_reason" => "Failed to upload file to the server"
            ];
        }
    }
}

$failed_files_count = count($failed_files);
if ($failed_files_count != 0) {
    $error_str = 'Failed to upload file(s): ';

    for ($i = 0; $i < $failed_files_count; $i++) {
        $failed_file = $failed_files[$i];
        $filename = $failed_file["filename"];
        $fail_reason = $failed_file["fail_reason"];

        if ($i == $failed_files_count - 1)
            $error_str .= "$filename (Reason: $fail_reason)";
        else
            $error_str .= "$filename (Reason: $fail_reason), ";
    }

    $_SESSION['current_status'] = $error_str;
    $_SESSION['status_type'] = 'error';
} else {
    $_SESSION['current_status'] = "File(s) successfully uploaded";
    $_SESSION['status_type'] = 'success';
}

// Redirect back to the ticket
header("Location: edit_ticket.php?id=$ticket_id");
exit;
