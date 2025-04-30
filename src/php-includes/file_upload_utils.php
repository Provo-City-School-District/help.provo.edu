<?php
function KiB(int $bytes)
{
    return $bytes * 1024;
}

function MiB(int $bytes)
{
    return KiB($bytes) * 1024;
}

function get_max_attachment_file_size()
{
    return MiB(10);
}

function get_max_file_size()
{
    return MiB(100);
}

function get_allowed_extensions()
{
    return ['jpg', 'jpeg', 'png', 'heic', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip', 'rar', '7z', 'tar', 'mp3', 'mp4', 'svg', 'mov'];
}

function get_allowed_mime_types()
{
    return [
        'image/jpeg',
        'image/png',
        'image/heic',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
        'application/zip',
        'application/vnd.rar',
        'application/x-7z-compressed',
        'application/x-tar',
        'audio/mpeg',
        'video/mp4',
        'video/quicktime',
        'image/svg+xml'
    ];
}

function compress_and_resize_image(string $image_path, string $image_type)
{
    $newWidth = 1500;

    // Create a new image from file
    switch ($image_type) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($image_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($image_path);
            break;
        default:
            return false;
    }

    // Get original dimensions
    $size = getimagesize($image_path);
    $oldWidth = $size[0];
    $oldHeight = $size[1];

    // Calculate new dimensions
    $ratio = $oldWidth / $oldHeight;
    $newHeight = $newWidth / $ratio;

    // Create a new true color image with the new dimensions
    $new_image = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG images
    if ($image_type == 'image/png') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Copy and resize the old image into the new image
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $newWidth, $newHeight, $oldWidth, $oldHeight);

    // Save the new image to the destination file
    switch ($image_type) {
        case 'image/jpeg':
            imagejpeg($new_image, $image_path, 75); // quality set to 75%
            break;
        case 'image/png':
            imagepng($new_image, $image_path);
            break;
    }

    // Free up memory
    imagedestroy($image);
    imagedestroy($new_image);

    return true;
}

function handleFileUploads($files, $ticket_id = null)
{
    $allowed_extensions = get_allowed_extensions();
    $max_file_size = get_max_file_size();
    $failed_files = [];
    $uploaded_files = [];

    // Loop through the uploaded files
    for ($i = 0; $i < count($files['attachment']['name']); $i++) {
        // Get the file name and temporary file path
        $fileName = $files['attachment']['name'][$i];
        $tmpFilePath = $files['attachment']['tmp_name'][$i];

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

        // remove commas from the filename
        $fileName = str_replace(',', '', $fileName);

        // Check if the file was uploaded successfully and has an allowed extension / file type
        // Will also check the file to validate the file is what it claims (eg: cant rename .exe to .png)
        if ($tmpFilePath != "") {
            if ($fileSize <= $max_file_size) {
                if (in_array($fileExtension, $allowed_extensions)) {

                    // Generate a unique file name
                    if ($ticket_id != null) {
                        $newFilePath = "/../uploads/" . $ticket_id . "-" . $fileName;
                    } else {
                        $newFilePath = "/../uploads/" . date('Ymd_Hi') . "-" . $fileName;
                    }
                    $absolute_path = from_root($newFilePath);

                    if ($fileType == "image/png" || $fileType == "image/jpeg")
                        compress_and_resize_image($tmpFilePath, $fileType);

                    // Move the file to the uploads directory
                    if (move_uploaded_file($tmpFilePath, $absolute_path)) {
                        // File was uploaded successfully, insert the file path into the database
                        $query = "UPDATE tickets SET attachment_path = 
                        (CASE WHEN attachment_path IS null OR attachment_path = '' THEN ? ELSE CONCAT(attachment_path, ',', ?) END)
                        WHERE id = ?";
                        $stmt = mysqli_prepare(HelpDB::get(), $query);
                        mysqli_stmt_bind_param($stmt, "ssi", $newFilePath, $newFilePath, $ticket_id);
                        mysqli_stmt_execute($stmt);

                        // Add the file name to the uploaded_files array
                        $uploaded_files[] = $newFilePath;
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
                    "fail_reason" => "File size is too large ($fileSize MiB > $max_file_size MiB)"
                ];
            }
        } else {
            $failed_files[] = [
                "filename" => $fileName,
                "fail_reason" => "Failed to upload file to the server"
            ];
        }
    }

    // Log the ticket changes
    if (isset($ticket_id)) {
        foreach ($uploaded_files as $fileName) {
            $field_name = 'Attachment';
            $oldValue = 'NA';
            logTicketChange(HelpDB::get(), $ticket_id, $_SESSION['username'] ?? 'System', $field_name, $oldValue, $fileName);
        }
    }

    return [$failed_files, $uploaded_files];
}
