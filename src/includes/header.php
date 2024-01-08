<?php
// include init file
include_once('init.php');
// includes functions file
include_once('functions.php');

// Function to check if the current URL matches any of the specified URLs
function isCurrentPage($urls)
{
    $currentPage = $_SERVER['REQUEST_URI'];
    foreach ($urls as $url) {
        if (strpos($currentPage, $url) !== false) {
            return true;
        }
    }
    return false;
}

// List of Ticket pages for which you want to display a ticket sub-menu
$ticketPages = array(
    '/tickets.php',
    '/edit_ticket.php',
    '/create_ticket.php',
    '/recent_tickets.php',
    '/search_tickets.php',
    '/ticket_history.php',
    '/flagged_tickets.php'
);

$is_logged_in = false;
//Checks logged in status and bounces you to the login page if not logged in
if (isset($_SESSION['username'])) {
    $is_logged_in = true;
} elseif (!endsWith($_SERVER['PHP_SELF'], 'index.php')) {
    header("Location: /index.php");
    exit();
}

// Get the current page URL
$current_page = $_SERVER['REQUEST_URI'];

// Define the URLs of the pages you want to highlight
$home_page_url = '/profile.php';
$ticket_page_url = '/tickets.php';
//$user_profile = '/profile.php';
$admin_page = '/admin.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help For Provo City School District</title>
    <link rel="stylesheet" href="/includes/js/dataTables-1.13.7/jquery.dataTables.min.css">
    <link rel="stylesheet" href="/includes/css/main.css?v=1.0.13">
    <link rel="icon" type="image/png" href="/includes/img/favicons/favicon-16x16.png" sizes="16x16">

    <?php
    //load color scheme if set. loads light scheme if not set
    if (isset($_SESSION['color_scheme'])) {
    ?>
        <link rel="stylesheet" type="text/css" href="/includes/css/variables-<?= $_SESSION['color_scheme'] ?>.css">
    <?php
    } else {
    ?>
        <link rel="stylesheet" type="text/css" href="/includes/css/variables-light.css">
    <?php
    }
    //load login page styles
    if ($_SERVER['REQUEST_URI'] === '/index.php' || $_SERVER['REQUEST_URI'] === '/') {
    ?>
        <link rel="stylesheet" type="text/css" href="/includes/css/login-styles.css">
    <?php
    }
    ?>
</head>

<body>
    <div id="wrapper">
        <header id="mainHeader">
            <a href="/profile.php">
                <img id="pcsd-logo" src="/includes/img/pcsd-logo-website-header-160w.png" alt="Provo City School District Logo" />
            </a>
            <?php
            if ($is_logged_in) {
            ?>
                <nav id="headerNav">
                    <?php
                    if ($_SESSION['permissions']['is_tech'] == 1) {
                    ?>
                        <a href="/profile.php">Profile</a>
                    <?php
                    }
                    ?>
                    <!-- <a href="/home.php">Home</a> -->

                    <a href="/tickets.php">Tickets</a>
                    <?php
                    if ($_SESSION['permissions']['is_admin'] == 1) {
                    ?>
                        <a href="/admin.php">Admin</a>
                    <?php
                    }
                    ?>

                    <a href="/controllers/logout.php">Logout</a>
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
                    <li><a href="/controllers/tickets/create_ticket.php">Create Ticket</a></li>

                    <?php
                    if ($_SESSION['permissions']['is_tech'] == 1) {
                        require_once("helpdbconnect.php");
                        $username = $_SESSION['username'];
                        $num_assigned_tickets = 0;
                        $num_flagged_tickets = 0;

                        /*
                        TODO: Figure out a good design to use the same query from assigned_tickets.php and
                        flagged_tickets.php so they aren't in two places. Maybe just a function.
                        */
                        $num_assigned_tickets_query = <<<STR
                            SELECT 1
                            FROM tickets
                            WHERE status NOT IN ('Closed', 'Resolved')
                            AND employee = ?
                            ORDER BY id ASC
                        STR;

                        $num_flagged_tickets_query = <<<STR
                        SELECT 1 FROM tickets 
                        WHERE
                            tickets.id in (
                                SELECT flagged_tickets.ticket_id from flagged_tickets WHERE flagged_tickets.user_id in (
                                    SELECT users.id FROM users WHERE users.username = ?
                                )
                            )
                        STR;

                        $assigned_stmt = mysqli_prepare($database, $num_assigned_tickets_query);
                        mysqli_stmt_bind_param($assigned_stmt, "s", $username);
                        $assigned_stmt_succeeded = mysqli_stmt_execute($assigned_stmt);
                        $assigned_res = mysqli_stmt_get_result($assigned_stmt);

                        if ($assigned_stmt_succeeded)
                            $num_assigned_tickets = mysqli_num_rows($assigned_res);


                        $flagged_stmt = mysqli_prepare($database, $num_flagged_tickets_query);
                        mysqli_stmt_bind_param($flagged_stmt, "s", $username);
                        $flagged_stmt_succeeded = mysqli_stmt_execute($flagged_stmt);
                        $flagged_res = mysqli_stmt_get_result($flagged_stmt);

                        if ($flagged_stmt_succeeded)
                            $num_flagged_tickets = mysqli_num_rows($flagged_res);
    
                        mysqli_stmt_close($assigned_stmt);
                        mysqli_stmt_close($flagged_stmt);
                    ?>
                    
                        <li><a href="/tickets.php">Assigned Tickets (<?= $num_assigned_tickets ?>)</a></li>
                        <li><a href="/controllers/tickets/flagged_tickets.php">Flagged Tickets (<?= $num_flagged_tickets ?>)</a></li>
                        <li><a href="/controllers/tickets/recent_tickets.php">Recent Tickets</a></li>
                        <li><a href="/controllers/tickets/search_tickets.php">Search Tickets</a></li>
                    <?php
                    } else {
                    ?>
                        <li><a href="/tickets.php">My Tickets</a></li>
                        <li><a href="/controllers/tickets/ticket_history.php">Ticket History</a></li>
                    <?php
                    }
                    if($_SESSION['permissions']['is_supervisor'] == 1) {
                        ?>
                        <li><a href="/tickets.php">Supervised Tickets</a></li>
                    <?php
                    } 
                    if($_SESSION['permissions']['is_location_manager'] == 1) {
                        ?>
                        <li><a href="/tickets.php">Location Tickets</a></li>
                    <?php
                    }  
                    ?>


                </ul>
            <?php
            }
            ?>