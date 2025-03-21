<?php
require_once('helpdbconnect.php');
require_once("block_file.php");
require_once("ticket_utils.php");
require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/base_variables.php");

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);
// Fetch the tech usernames
$department = $_SESSION['department'] ?? null;
$can_see_all_techs = $_SESSION['permissions']['can_see_all_techs'] ?? 0;

// Fetch the tech usernames
if ($can_see_all_techs) {
    $tech_usernames = get_tech_usernames();
} else {
    $tech_usernames = get_tech_usernames($department);
}

$can_input_maintenance = get_user_setting(get_id_for_user($_SESSION['username']), 'can_input_maintenance_tickets');

// Fetch the departments
$department_result = HelpDB::get()->execute_query("SELECT * FROM locations WHERE is_department = TRUE AND is_archived = FALSE ORDER BY location_name ASC");
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
            $_GET['location'] = 1896;
        } else {
            $loc = get_fast_client_location($_SESSION["username"]);
            if ($row['sitenumber'] == $loc) {
                $select = true;
            }
        }
    }

    $depts[] = ["site_number" => $row["sitenumber"], "site_name" => $row["location_name"], "select" => $select];
}

// Fetch the locations
$location_result = HelpDB::get()->execute_query("SELECT * FROM locations WHERE is_department = FALSE AND is_archived = FALSE ORDER BY location_name ASC");
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

// Fetch request types
$topLevelQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_parent IS NULL ORDER BY request_name";
$topLevelResult = HelpDB::get()->query($topLevelQuery);

$requestTypes = [];

while ($topLevelRow = $topLevelResult->fetch_assoc()) {
    $topLevelId = $topLevelRow['request_id'];
    $requestTypes[$topLevelId] = [
        'id' => $topLevelId,
        'name' => $topLevelRow['request_name'],
        'children' => []
    ];

    // Fetch the child request types
    $childQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_parent = $topLevelId ORDER BY request_name";
    $childResult = HelpDB::get()->query($childQuery);

    while ($childRow = $childResult->fetch_assoc()) {
        $childId = $childRow['request_id'];
        $requestTypes[$topLevelId]['children'][$childId] = [
            'id' => $childId,
            'name' => $childRow['request_name'],
            'children' => []
        ];

        // Fetch the grandchild request types
        $grandchildQuery = "SELECT * FROM request_type WHERE is_archived = 0 AND request_parent = $childId ORDER BY request_name";
        $grandchildResult = HelpDB::get()->query($grandchildQuery);

        while ($grandchildRow = $grandchildResult->fetch_assoc()) {
            $grandchildId = $grandchildRow['request_id'];
            $requestTypes[$topLevelId]['children'][$childId]['children'][$grandchildId] = [
                'id' => $grandchildId,
                'name' => $grandchildRow['request_name']
            ];
        }
    }
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
    'requestTypes' => $requestTypes,
    'tech_usernames' => $tech_usernames,
    'username' => $_SESSION['username'],
    'can_input_maintenance' => $can_input_maintenance,
    '_get' => $_GET
]);
