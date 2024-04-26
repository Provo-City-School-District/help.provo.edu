<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');

if ($_SESSION['permissions']['is_admin'] != 1) {
    echo 'You do not have permission to use this form.';
    exit;
}

// Get the exclude day ID from the query string
$id = trim(htmlspecialchars($_GET['id']));

// Delete the exclude day from the database
$query = "DELETE FROM exclude_days WHERE id = ?";
$stmt = mysqli_prepare($database, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Redirect back to the exclude days page
header('Location: /admin.php');
exit();
