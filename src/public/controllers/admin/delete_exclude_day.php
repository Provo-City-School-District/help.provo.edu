<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');
require_once('ticket_utils.php');

// Check if user is Admin
$is_admin = get_user_setting(get_id_for_user($_SESSION['username']), "is_admin") ?? 0;
if ($is_admin != 1) {
    // User is not an admin
    echo 'You do not have permission to view this page.';
    exit;
}

// Get the exclude day ID from the query string
$id = trim(htmlspecialchars($_GET['id']));

if ($id == null || $id == "") {
    $error = 'Exclude date ID was invalid';
    $_SESSION['current_status'] = $error;
    $_SESSION['status_type'] = 'error';
    header('Location: /controllers/admin/exclude_days_management.php');
    exit;
}

// Delete the exclude day from the database
$query = "DELETE FROM exclude_days WHERE id = ?";
$stmt = mysqli_prepare(HelpDB::get(), $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Redirect back to the exclude days page
header('Location: /controllers/admin/exclude_days_management.php');
exit();
