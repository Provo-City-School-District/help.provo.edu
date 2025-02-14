<?php
require_once("block_file.php");
require_once('init.php');
require_once('helpdbconnect.php');

if ($_SESSION['permissions']['is_admin'] != 1) {
    echo 'You do not have permission to use this form.';
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
        header('Location: /admin.php');
        exit;
    }
    // Insert the exclude day into the database
    $query = "INSERT INTO exclude_days (exclude_day, entered_by) VALUES (?, ?)";
    $stmt = mysqli_prepare(HelpDB::get(), $query);
    mysqli_stmt_bind_param($stmt, "ss", $exclude_day, $entered_by);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header('Location: /admin.php');
    exit;
}
