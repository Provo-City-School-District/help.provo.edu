<?php
// include init file
include_once('init.php');
// includes functions file
include_once('functions.php');


//Checks logged in status and bounces you to the login page if not logged in
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
            <?php
            // Check if the current page matches any of the specified URLs
            if (isCurrentPage($ticketPages)) {
                // Display the sub-menu here
            ?>
                <ul id="subMenu">
                    <li><a href="<?= $root_domain; ?>/tickets.php">My Tickets</a></li>
                    <li><a href="#">Group Tickets</a></li>
                    <li><a href="#">Recent Tickets</a></li>
                    <li><a href="#">Search Tickets</a></li>
                    <li><a href="<?= $root_domain; ?>/controllers/tickets/create_ticket.php">Create Ticket</a></li>
                </ul>
            <?php
            }
            ?>