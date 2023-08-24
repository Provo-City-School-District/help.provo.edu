<?php
$root_domain = 'http://localhost:8080';
// Checks if Session exists, if not starts one.
if (!session_id()) {
    session_start();
}
//checks if user is logged in by checking $_SESSION variable for a username set at login and checks that we aren't currently on the login page. if both are true will send user to login page.
if (!isset($_SESSION['username']) && !endsWith($_SERVER['PHP_SELF'], 'index.php')) {
    header('Location: index.php');
    exit();
}
//Checks current page in $_SERVER variable.
function endsWith($haystack, $needle) {
    return substr($haystack, -strlen($needle)) === $needle;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>help.provo.edu</title>
    <link rel="stylesheet" href="<?= $root_domain; ?>/includes/css/main.css">
</head>

<body>
    <header>
        Main Header
    </header>
    <main>

