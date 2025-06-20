<?php
// include init file
require_once('init.php');
// includes functions file
include_once('functions.php');
include_once('helpdbconnect.php');
require("time_utils.php");
require_once("ticket_utils.php");

// Function to check if the current URL matches any of the specified URLs
function is_current_page($urls)
{
    $currentPage = $_SERVER['REQUEST_URI'];
    foreach ($urls as $url) {
        if (strpos($currentPage, $url) !== false) {
            return true;
        }
    }
    return false;
}

$username = "";
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
}

$subord_result = HelpDB::get()->execute_query(
    "SELECT COUNT(*) AS supervisor_count FROM user_settings WHERE supervisor_username = ?",
    [$username]
);
$subord_row = $subord_result->fetch_assoc();
$subord_count = $subord_row['supervisor_count'];

// List of Ticket pages for which you want to display a ticket sub-menu
$ticketPages = array(
    '/edit_ticket.php',
    '/create_ticket.php',
    '/search_tickets.php',
    '/location_tickets.php'
);
// List of Supervisor pages for which you want to display a supervisor sub-menu
$supervisorPages = array();



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
// $home_page_url = '/profile.php';
// $ticket_page_url = '/tickets.php';
//$user_profile = '/profile.php';
// $admin_page = '/admin.php';

if (session_logged_in() && session_is_intern()) {
    $num_assigned_intern_tickets = 0;

    $num_assigned_intern_tickets_query = <<<QUERY
        SELECT COUNT(1) FROM tickets WHERE intern_visible = 1 AND location = ?;
    QUERY;

    $intern_site = $_SESSION["permissions"]["intern_site"];
    if (!isset($intern_site) || $intern_site == 0) {
        log_app(LOG_INFO, "[header.php] intern_site not set. failed to get ticket count");
    }
    $ticket_result = HelpDB::get()->execute_query($num_assigned_intern_tickets_query, [$intern_site]);
    $ticket_result_data = mysqli_fetch_assoc($ticket_result);
    $num_assigned_intern_tickets = $ticket_result_data['COUNT(1)'];
}

$num_assigned_tasks_query = "SELECT COUNT(*) FROM ticket_tasks WHERE (NOT completed AND assigned_tech = ?)";
$num_assigned_tasks_result = HelpDB::get()->execute_query($num_assigned_tasks_query, [$username]);

$num_assigned_tasks = $num_assigned_tasks_result->fetch_column(0);

$num_subordinate_tickets_query = <<<STR
    SELECT COUNT(*) FROM alerts
    INNER JOIN users ON users.username = alerts.employee
    INNER JOIN user_settings ON user_settings.user_id = users.id
    WHERE user_settings.supervisor_username = ?
STR;


if (isset($_SESSION['username']) && $_SESSION['username'] != "") {
    $num_subordinate_tickets_result = HelpDB::get()->execute_query($num_subordinate_tickets_query, [$username]);
    $num_subordinate_tickets = $num_subordinate_tickets_result->fetch_column(0);
}

$num_project_tickets = 0;

$num_project_tickets_query = <<<STR
    SELECT COUNT(*) FROM tickets
    WHERE status NOT IN ('Closed', 'Resolved')
    AND priority = 30
    AND employee = ?
    ORDER BY id ASC
STR;
$num_project_tickets_result = HelpDB::get()->execute_query($num_project_tickets_query, [$username]);
$num_project_tickets = $num_project_tickets_result->fetch_column(0);


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help For Provo City School District</title>
    <link rel="stylesheet" href="/includes/js/external/dataTables/datatables.min.css">
    <link rel="stylesheet" href="/includes/css/main.css?v=<?= $app_version; ?>">
    <link rel="icon" type="image/png" href="/includes/img/favicons/favicon-16x16.png" sizes="16x16">
    <link rel="stylesheet" href="/includes/css/external/jquery-ui.min.css">
    <link rel="stylesheet" type="text/css" href="/includes/css/variables-common.css?v=<?= $app_version; ?>">
    <?php
    //load color scheme if set. loads light scheme if not set
    if (isset($_SESSION['color_scheme'])) {
    ?>
        <link rel="stylesheet" type="text/css" href="/includes/css/variables-<?= $_SESSION['color_scheme'] ?>.css?v=<?= $app_version; ?>">
    <?php
    } else {
    ?>
        <link rel="stylesheet" type="text/css" href="/includes/css/variables-light.css?v=<?= $app_version; ?>">
    <?php
    }
    //load login page styles
    if ($_SERVER['REQUEST_URI'] === '/index.php' || $_SERVER['REQUEST_URI'] === '/') {
    ?>
        <link rel="stylesheet" type="text/css" href="/includes/css/login-styles.css?v=<?= $app_version; ?>">
    <?php
    }
    ?>
    <link href="/includes/css/external/lightbox.css?v=<?= $app_version; ?>" rel="stylesheet" />
    <link rel="stylesheet" href="/includes/css/external/tinymce-prism.css?v=<?= $app_version; ?>">
</head>

<body>
    <div id="wrapper">
        <header id="mainHeader">
            <a href="/tickets.php">
                <img id="pcsd-logo" src="/includes/img/pcsd-logo-website-header-160w.png" alt="Provo City School District Logo" />
            </a>
            <?php
            if ($is_logged_in) {
            ?>
                <div id="headerNav">
                    <span id="mobileMenu">&#9776; Menu</span>
                    <nav id="mainNav">
                        <a href="/profile.php">Profile</a>
                        <a href="/tickets.php">Tickets</a>
                        <?php
                        if ($_SESSION['permissions']['is_supervisor'] == 1) {
                        ?>
                            <a href="/supervisor.php">Supervisor</a>
                        <?php
                        }
                        ?>

                        <?php
                        if ($_SESSION['permissions']['is_admin'] == 1) {
                        ?>
                            <a href="/admin.php">Admin</a>
                        <?php
                        }
                        ?>
                        <?php
                        if ($_SESSION['permissions']['view_stats'] == 1 || $_SESSION['permissions']['is_admin'] == 1) {
                        ?>
                            <a href="/stats.php">Stats</a>
                        <?php
                        }
                        ?>
                        <a href="/controllers/logout.php">Logout</a>
                    </nav>

                </div>
                <div id="dayWOHours">
                    <?php
                    $day_timestamp = strtotime("today");
                    $day_ticket_times = get_note_time_for_days($_SESSION["username"], [$day_timestamp]);

                    $day_time_min = $day_ticket_times[0] / 60;
                    ?>
                    Today's WO time:
                    <pre><?= number_format($day_time_min, 2); ?> hrs</pre>
                </div>
            <?php
            }
            ?>
        </header>
        <main id="pageContent">
            <?php
            // Check if the current page matches any of the specified URLs
            if (is_current_page($ticketPages)) {
                // Display the sub-menu here
            ?>
                <ul id="subMenu">
                    <li><a href="/controllers/tickets/create_ticket.php">Create Ticket</a></li>

                    <?php
                    if ($_SESSION['permissions']['is_supervisor'] == 1 && $subord_count > 0) {
                    ?>
                        <li><a href="/controllers/tickets/subordinate_tickets.php">Subordinate Tickets (<?= $num_subordinate_tickets ?>) </a></li>
                    <?php
                    }
                    if ($_SESSION['permissions']['is_location_manager'] == 1) {
                    ?>
                        <li><a href="/controllers/tickets/location_tickets.php">Location Tickets</a></li>
                    <?php
                    }
                    if ($_SESSION['permissions']['is_tech'] == 1) {
                        require_once("helpdbconnect.php");
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

                        $assigned_stmt = mysqli_prepare(HelpDB::get(), $num_assigned_tickets_query);
                        mysqli_stmt_bind_param($assigned_stmt, "s", $username);
                        $assigned_stmt_succeeded = mysqli_stmt_execute($assigned_stmt);
                        $assigned_res = mysqli_stmt_get_result($assigned_stmt);

                        if ($assigned_stmt_succeeded)
                            $num_assigned_tickets = mysqli_num_rows($assigned_res);


                        $flagged_stmt = mysqli_prepare(HelpDB::get(), $num_flagged_tickets_query);
                        mysqli_stmt_bind_param($flagged_stmt, "s", $username);
                        $flagged_stmt_succeeded = mysqli_stmt_execute($flagged_stmt);
                        $flagged_res = mysqli_stmt_get_result($flagged_stmt);

                        if ($flagged_stmt_succeeded)
                            $num_flagged_tickets = mysqli_num_rows($flagged_res);

                        mysqli_stmt_close($assigned_stmt);
                        mysqli_stmt_close($flagged_stmt);
                    ?>

                        <li><a href="/tickets.php">My Tickets (<?= $num_assigned_tickets ?>)</a></li>

                        <?php
                        if ($num_assigned_tasks != 0) {
                        ?>
                            <li><a href="/tasks.php">My Tasks (<?= $num_assigned_tasks ?>)</a></li>
                        <?php
                        }
                        if ($num_project_tickets != 0) {
                        ?>
                            <li><a href="/controllers/tickets/project_tickets.php">My Projects (<?= $num_project_tickets ?>)</a></li>
                        <?php
                        }
                        if ($num_flagged_tickets != 0) {
                        ?>
                            <li><a href="/controllers/tickets/flagged_tickets.php">Flagged Tickets (<?= $num_flagged_tickets ?>)</a></li>
                        <?php
                        }
                        ?>
                        <li><a href="/controllers/tickets/recent_tickets.php">Recent Tickets</a></li>
                        <li><a href="/controllers/tickets/search_tickets.php">Search Tickets</a></li>
                    <?php
                    } else {
                    ?>
                        <?php if (session_is_intern()): ?>
                            <li><a href="/controllers/tickets/intern_tickets.php">Intern Tickets (<?= $num_assigned_intern_tickets ?>)</a></li>
                        <?php else: ?>
                            <li><a href="/tickets.php">My Tickets</a></li>
                        <?php endif; ?>
                        <li><a href="/controllers/tickets/ticket_history.php">Ticket History</a></li>
                    <?php
                    }
                    ?>


                </ul>
            <?php
            }
            ?>