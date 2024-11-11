<?php
require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/ticket_base_variables.php");
require_once "ticket_utils.php";
require_once from_root('/../php-includes/swdbconnect.php');
require "sanitization_utils.php";

//====================================================================================================
// init variables
//====================================================================================================
$search_id = '';
$search_name = '';
$search_location = '';
$search_employee = '';
$search_client = '';
$search_status = '';
$search_priority = '';
$search_start_date = '';
$search_end_date = '';
$dates_searched = [];
$pageScripts = '/includes/js/pages/search_tickets.js';

//// I dont think this is needed here anymore.
////
// Query the locations table to get the location information
// $location_query = "SELECT sitenumber, location_name FROM locations ORDER BY location_name ASC";
// $location_result = HelpDB::get()->execute_query($location_query);

//====================================================================================================
// parse the search form if it was submitted
//====================================================================================================
$combined_results = [];
if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    if (!empty($_GET)) {
        // Get the search terms from the form
        $search_id = isset($_GET['search_id']) ? mysqli_real_escape_string(HelpDB::get(), $_GET['search_id']) : '';
        $search_name = isset($_GET['search_name']) ? mysqli_real_escape_string(HelpDB::get(), $_GET['search_name']) : '';
        $search_location = isset($_GET['search_location']) ? mysqli_real_escape_string(HelpDB::get(), $_GET['search_location']) : '';
        $search_employee = isset($_GET['search_employee']) ? mysqli_real_escape_string(HelpDB::get(), $_GET['search_employee']) : '';
        $search_client = isset($_GET['search_client']) ? mysqli_real_escape_string(HelpDB::get(), $_GET['search_client']) : '';
        $search_status = isset($_GET['search_status']) ? mysqli_real_escape_string(HelpDB::get(), $_GET['search_status']) : '';
        $search_priority = isset($_GET['priority']) ? mysqli_real_escape_string(HelpDB::get(), $_GET['priority']) : '';
        $search_start_date = isset($_GET['start_date']) ? mysqli_real_escape_string(HelpDB::get(), $_GET['start_date']) : '';
        $search_end_date = isset($_GET['end_date']) ? mysqli_real_escape_string(HelpDB::get(), $_GET['end_date']) : '';
        $dates_searched = isset($_GET['dates']) ? $_GET['dates'] : [];
        $search_archived = isset($_GET['search_archived']) ? mysqli_real_escape_string(HelpDB::get(), $_GET['search_archived']) : '';

        // Query the archived_location_id values for the given sitenumber
        $archived_location_ids = array();
        $arch_location_query = "SELECT archived_location_id FROM locations WHERE sitenumber = '$search_location'";
        $arch_location_result = HelpDB::get()->query($arch_location_query);
        if ($arch_location_result->num_rows > 0) {
            while ($arch_row = $arch_location_result->fetch_assoc()) {
                $archived_location_ids[] = $arch_row['archived_location_id'];
            }
        }
        require_once('search_query_builder.php');
        // Execute the SQL query to search for matching tickets
        $ticket_result = mysqli_query(HelpDB::get(), $ticket_query);

        if ($search_archived == 1) {
            //include archived tickets in search
            require_once('search_archived_query_builder.php');
            $old_ticket_result = mysqli_query(SolarWindsDB::get(), $old_ticket_query);
        }

        // Combine the results from both queries into a single array
        while ($row = mysqli_fetch_assoc($ticket_result)) {
            // fix '&quot' and other things appearing
            $row["name"] = strip_tags(html_entity_decode($row["name"]));
            $row["description"] = strip_tags(html_entity_decode($row["description"]));
            $row["client_name"] = get_local_name_for_user($row["client"]);
            $combined_results[] = $row;
        }

        //add in archived tickets if the checkbox is checked
        if ($search_archived == 1) {
            while ($row = mysqli_fetch_assoc($old_ticket_result)) {
                $row["client_name"] = get_sw_client_name($row["CLIENT_ID"]);
                $combined_results[] = $row;
            }
        }
    }
}

//====================================================================================================
//Logic
//====================================================================================================
// Check if the user is a technician
$isTech = user_is_tech($_SESSION['username']);

// Query the locations table to get the departments
$department_query = "SELECT * FROM locations WHERE is_department = TRUE ORDER BY location_name ASC";
$department_result = HelpDB::get()->execute_query($department_query);
$departments = mysqli_fetch_all($department_result, MYSQLI_ASSOC);

// Query the locations table to get the locations
$location_query = "SELECT * FROM locations WHERE is_department = FALSE ORDER BY location_name ASC";
$location_result = HelpDB::get()->execute_query($location_query);
$locations = mysqli_fetch_all($location_result, MYSQLI_ASSOC);

//===================================================
// Get the usernames for the search form
//===================================================
$usernamesQuery = "SELECT username,is_tech FROM users ORDER BY username ASC";
$usernamesResult = HelpDB::get()->execute_query($usernamesQuery);

if (!$usernamesResult) {
    die('Error fetching usernames: ' . mysqli_error(HelpDB::get()));
}

// Store the usernames in an array
$usernames = [];
$tech_display_names = [];
while ($usernameRow = mysqli_fetch_assoc($usernamesResult)) {

    if ($usernameRow['is_tech'] == 1) {
        $name = get_local_name_for_user($usernameRow['username']);
        $firstname = ucwords(strtolower($name["firstname"]));
        $lastname = ucwords(strtolower($name["lastname"]));
        $display_string = $firstname . " " . $lastname . " - " . location_name_from_id(get_fast_client_location($username) ?: "");
        $tech_display_names[] = [$display_string, $usernameRow['username']];
    } else {
        $usernames[] = strtolower($usernameRow['username']);
    }
}

asort($tech_display_names);


//====================================================================================================
//Display View
//====================================================================================================
// Load the Twig template
$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);
// Add custom functions and filters to twig
$fetchTechFunc = new Twig\TwigFunction('get_tech_name_from_id_user', function ($tech_sw_id, $archived = false) {
    return get_tech_name_from_id_user($tech_sw_id, $archived);
});
$fetchLocFunc = new Twig\TwigFunction('get_location_name_from_id', function ($location_sw_id, $archived = false) {
    return get_location_name_from_id($location_sw_id, $archived);
});
$twig->addFilter(new \Twig\TwigFilter('priorityTypes', 'priorityTypes'));
$twig->addFilter(new \Twig\TwigFilter('get_request_type_by_id', 'get_request_type_by_id'));
$twig->addFunction($fetchTechFunc);
$twig->addFunction($fetchLocFunc);

// Render the template
echo $twig->render('search_tickets.twig', [
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

    // ticket_base variables
    'subord_count' => $subord_count,
    'num_assigned_tickets' => $num_assigned_tickets,
    'num_flagged_tickets' => $num_flagged_tickets,
    'num_assigned_tasks' => $num_assigned_tasks,
    'num_subordinate_tickets' => $num_subordinate_tickets,

    //page variables
    'isTech' => $isTech,
    'departments' => $departments,
    'locations' => $locations,
    'tech_display_names' => $tech_display_names,
    'page_scripts' => $pageScripts,
    'results' => $combined_results,

    //search form variables
    'search_id' => $search_id,
    'search_name' => $search_name,
    'search_location' => $search_location,
    'search_status' => $search_status,
    'search_priority' => $search_priority,
    'search_start_date' => $search_start_date,
    'search_end_date' => $search_end_date,
    'dates_searched' => $dates_searched,
    'search_archived' => isset($search_archived) && $search_archived != null ? 1 : 0,
    'search_employee' => $search_employee,
    'search_client' => $search_client,
    'priorityTypes' => $priorityTypes,

]);

//====================================================================================================
// Functions
//====================================================================================================
// TODO could cache these
// TODO2: similar function in ticket_utils.php. lets consolidate
function get_location_name_from_id($location_sw_id, $archived = false)
{
    $location_name = "";
    if ($location_sw_id === null || $location_sw_id === "") {
        return "Unknown Location";
    }
    if ($archived) {
        $location_name_result = HelpDB::get()->execute_query("SELECT location_name FROM locations WHERE archived_location_id = ?", [$location_sw_id]);
    } else {
        $location_name_result = HelpDB::get()->execute_query("SELECT location_name FROM locations WHERE sitenumber = ?", [$location_sw_id]);
    }

    $location_name_data = mysqli_fetch_assoc($location_name_result);
    if (is_array($location_name_data) && isset($location_name_data["location_name"])) {
        $location_name = trim($location_name_data["location_name"]);
    }

    return $location_name;
}

function get_sw_client_name(string $sw_client_id)
{
    $tech_name_result = SolarWindsDB::get()->execute_query("SELECT FIRST_NAME, LAST_NAME FROM client WHERE CLIENT_ID = ?", [$sw_client_id]);
    $tech_name_data = $tech_name_result->fetch_assoc();
    $first_name = ucfirst(strtolower(trim($tech_name_data["FIRST_NAME"])));
    $last_name = ucfirst(strtolower(trim($tech_name_data["LAST_NAME"])));
    return ["firstname" => $first_name, "lastname" => $last_name];
}

// TODO could cache these
// TODO2: similar function in ticket_utils.php. lets consolidate
function get_tech_name_from_id_user(string $tech_sw_id, $archived = false)
{
    if ($archived) {
        $tech_name_result = SolarWindsDB::get()->execute_query("SELECT FIRST_NAME, LAST_NAME FROM tech WHERE CLIENT_ID = ?", [$tech_sw_id]);
        $tech_name_data = mysqli_fetch_assoc($tech_name_result);
        if (is_array($tech_name_data)) {
            // Convert to lowercase and then capitalize each word
            $first_name = ucwords(strtolower(trim($tech_name_data["FIRST_NAME"])));
            $last_name = ucwords(strtolower(trim($tech_name_data["LAST_NAME"])));
            $tech_name = $first_name . " " . $last_name;
        } else {
            $tech_name = "Unknown Technician";
        }
    } else {
        $tech_name_result = HelpDB::get()->execute_query("SELECT firstname, lastname FROM users WHERE username = ?", [$tech_sw_id]);
        $tech_name_data = mysqli_fetch_assoc($tech_name_result);
        if (is_array($tech_name_data)) {
            // Convert to lowercase and then capitalize each word
            $first_name = ucwords(strtolower(trim($tech_name_data["firstname"])));
            $last_name = ucwords(strtolower(trim($tech_name_data["lastname"])));
            $tech_name = $first_name . " " . $last_name;
        } else {
            $tech_name = "Unknown Technician";
        }
    }


    return $tech_name;
}

// TODO could cache these
// TODO2: similar function in ticket_utils.php. lets consolidate
function get_request_type_by_id(int $request_type_id)
{
    $request_type_result = HelpDB::get()->execute_query("SELECT request_name FROM request_type WHERE request_id = ?", [$request_type_id]);
    $request_type_data = mysqli_fetch_assoc($request_type_result);

    // Correctly placed check if $request_type_data is not null and is an array before accessing its elements
    if (is_array($request_type_data) && isset($request_type_data["request_name"])) {
        $request_type = trim($request_type_data["request_name"]);
    } else {
        // Handle the case where no data is found or $request_type_data is null
        $request_type = "Other";
    }

    return $request_type;
}
