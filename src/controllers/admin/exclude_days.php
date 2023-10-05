<?php
require_once('../../includes/init.php');
require_once('../../includes/helpdbconnect.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the exclude day from the form data
    $exclude_day = trim(htmlspecialchars($_POST['exclude_day']));

    // Insert the exclude day into the database
    $query = "INSERT INTO exclude_days (exclude_day) VALUES (?)";
    $stmt = mysqli_prepare($database, $query);
    mysqli_stmt_bind_param($stmt, "s", $exclude_day);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header('Location: ../../admin.php');
    exit;
}
