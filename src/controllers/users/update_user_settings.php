<?php
require_once('init.php');
require_once('helpdbconnect.php');
// Check if the user ID is set
if (!isset($_POST['id'])) {
    die("User ID not set");
}
$user_id = $_POST['id'];
//catch changes from the users profile page that the user is allowed to change.
if ($_POST['referer'] == 'profile.php') {

    // Retrieve the color_scheme from the form submission
    $color_scheme = $_POST['color_scheme'] ?? 'system';

    // update color scheme with the new one. takes effect immediately
    $_SESSION['color_scheme'] = $color_scheme;

    // Update the color_scheme in the database
    $query = "UPDATE users SET color_scheme = ? WHERE id = ?";
    $stmt = mysqli_prepare($database, $query);
    mysqli_stmt_bind_param($stmt, "ss", $color_scheme, $user_id);

    $res = mysqli_stmt_execute($stmt);
    if ($res) {
        $_SESSION['current_status'] = "User updated successfully";
        $_SESSION['status_type'] = 'success';
    } else {
        $_SESSION['current_status'] = "Failed to update user: ".mysqli_error();
        $_SESSION['status_type'] = 'error';
    }
    header('Location: /profile.php');
    exit();
}
