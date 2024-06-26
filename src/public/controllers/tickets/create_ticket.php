<?php
require_once('helpdbconnect.php');
require_once("block_file.php");
require_once("ticket_utils.php");
require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/base_variables.php");

if (!session_id())
	session_start();

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
	'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);

$tech_usernames = get_tech_usernames();

$department_result = HelpDB::get()->execute_query("SELECT * FROM locations WHERE is_department = TRUE ORDER BY location_name ASC");
$depts = [];
while ($row = mysqli_fetch_assoc($department_result)) {
    $select = false;
    if (isset($_GET['location']) && $row['sitenumber'] == $_GET['location']) {
        $select = true;
    } else {
        if (session_is_tech()) {
            // Check if row == Technology
            if ($row['sitenumber'] == 1897) {
                $select = true;
            }
        } else {
            $loc = get_fast_client_location($_SESSION["username"]);
            if ($row['sitenumber'] == $loc) {
                $select = true;
            }
        }
    }

    $depts[] = ["site_number" => $row["sitenumber"], "site_name" => $row["location_name"], "select" => $select];
}

$location_result = HelpDB::get()->execute_query("SELECT * FROM locations WHERE is_department = FALSE ORDER BY location_name ASC");
$locations = [];
while ($row = mysqli_fetch_assoc($location_result)) {
    $select = false;
    if (isset($_GET['location']) && $row['sitenumber'] == $_GET['location']) {
        $select = true;
    } else if (!session_is_tech()) {
        $loc = get_fast_client_location($_SESSION["username"]);
        if ($row['sitenumber'] == $loc) {
            $select = true;
        }
    }

    $locations[] = ["site_number" => $row["sitenumber"], "site_name" => $row["location_name"], "select" => $select];
}

echo $twig->render('create_ticket.twig', [
	// base variables
	'color_scheme' => $color_scheme,
	'current_year' => $current_year,
	'user_permissions' => $permissions,
	'wo_time' => $wo_time,
	'user_pref' => $user_pref,
	'ticket_limit' => $ticket_limit,
	'status_alert_type' => $status_alert_type,
	'status_alert_message' => $status_alert_message,
	'app_version' => $app_version,

    // create_ticket variables
    'depts' => $depts,
    'locations' => $locations,
    'tech_usernames' => $tech_usernames,
    'username' => $_SESSION['username'],
    '_get' => $_GET
]);
