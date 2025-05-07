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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the exclude day from the form data
    $exclude_day = trim(htmlspecialchars($_POST['exclude_day']));
    $entered_by = trim(htmlspecialchars($_POST['username']));
    if ($exclude_day == null || $exclude_day == "") {
        $error = 'Exclude date was invalid';
        $_SESSION['current_status'] = $error;
        $_SESSION['status_type'] = 'error';
        header('Location: /controllers/admin/exclude_days_management.php');
        exit;
    }
    // Insert the exclude day into the database
    $query = "INSERT INTO exclude_days (exclude_day, entered_by) VALUES (?, ?)";
    $stmt = mysqli_prepare(HelpDB::get(), $query);
    mysqli_stmt_bind_param($stmt, "ss", $exclude_day, $entered_by);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header('Location: /controllers/admin/exclude_days_management.php');
    exit;
}
