<?php
$root_domain = getenv('ROOTDOMAIN');
$is_logged_in = false;
// Checks if Session exists, if not starts one.
if (!session_id()) {
    session_start();
}
//Checks current page in $_SERVER variable.
function endsWith($haystack, $needle)
{
    return substr($haystack, -strlen($needle)) === $needle;
}

if (isset($_SESSION['username'])) {
    $is_logged_in = true;
} elseif (!endsWith($_SERVER['PHP_SELF'], 'index.php')) {
    header('Location: index.php');
    exit();
}

// Get the current page URL
$current_page = $_SERVER['REQUEST_URI'];

// Define the URLs of the pages you want to highlight
$home_page_url = '/home.php';
$ticket_page_url = '/tickets.php';
$user_profile = '/profile.php';
$admin_page = '/admin.php';

// Function to check if the current page matches a given URL
function isActivePage($current_page, $page_url)
{
    return ($current_page === $page_url) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>| Help For Provo City School District</title>
    <link rel="stylesheet" href="<?= $root_domain; ?>/includes/css/main.css">
    <?php
        if ($_SERVER['REQUEST_URI'] === '/index.php' || $_SERVER['REQUEST_URI'] === '/') {
            ?>
            <link rel="stylesheet" type="text/css" href="<?= $root_domain; ?>/includes/css/login-styles.css">
            <?php
        }
    ?>
</head>

<body>
    <div id="wrapper">
        <header id="mainHeader">
            <a href="home.php">
                <img src="<?= $root_domain; ?>/includes/img/pcsd-logo-website-header-160w.png" alt="Provo City School District Logo" />
            </a>
            <?php
            if ($is_logged_in) {
            ?>
                <nav id="headerNav">
                    <a class="<?php echo isActivePage($current_page, $home_page_url); ?>" href="<?= $root_domain; ?>/home.php">Home</a>
                    <a class="<?php echo isActivePage($current_page, $ticket_page_url); ?>" href="<?= $root_domain; ?>/tickets.php">Tickets</a>
                    <a class="<?php echo isActivePage($current_page, $user_profile); ?>" href="<?= $root_domain; ?>/profile.php">Profile</a>
                    <?php
                    if ($_SESSION['permissions']['is_admin'] == 1) {
                    ?>
                        <a class="<?php echo isActivePage($current_page, $admin_page); ?>" href="<?= $root_domain; ?>/admin.php">Admin</a>
                    <?php
                    }
                    ?>

                    <a href="<?= $root_domain; ?>/controllers/logout.php">Logout</a>
                </nav>
            <?php
            }
            ?>
        </header>
        <main id="pageContent">