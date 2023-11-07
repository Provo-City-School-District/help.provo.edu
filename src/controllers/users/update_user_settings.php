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

    // Update the color_scheme in the database
    $query = "UPDATE users SET color_scheme = ? WHERE id = ?";
    $stmt = mysqli_prepare($database, $query);
    mysqli_stmt_bind_param($stmt, "ss", $color_scheme, $user_id);
    mysqli_stmt_execute($stmt);

    $_SESSION['color_scheme'] = $color_scheme;
    $_SESSION['user_updated'] = 'User updated successfully';
    header('Location: /profile.php');
    exit();
}
