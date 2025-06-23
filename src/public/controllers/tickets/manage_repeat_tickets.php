<?php
require 'block_file.php';
require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/base_variables.php");
require_once 'ticket_utils.php';




// Initialize Twig
$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);
$twig->addFunction(new \Twig\TwigFunction('location_name_from_id', 'location_name_from_id'));
$twig->addFunction(new \Twig\TwigFunction('request_name_for_type', 'request_name_for_type'));


// initialize variables
$user_id = get_id_for_user($_SESSION["username"]);





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





//handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
    if ($action === 'insert_repeat_ticket') {

        $title          = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
        $description    = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
        $department     = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_SPECIAL_CHARS);
        $room           = filter_input(INPUT_POST, 'room', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $location       = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $cc             = filter_input(INPUT_POST, 'cc', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $client         = filter_input(INPUT_POST, 'client', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $phone_number   = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $request_type   = filter_input(INPUT_POST, 'request_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $interval_type  = filter_input(INPUT_POST, 'interval_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $interval_value = filter_input(INPUT_POST, 'interval_value', FILTER_VALIDATE_INT);
        $next_run_date  = filter_input(INPUT_POST, 'next_run_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        // Client Defaults to current user if not provided
        if (empty($client)) {
            $client = $_SESSION["username"];
        }

        // Provide a default for interval_value if validation fails
        if ($interval_value === false || $interval_value < 1) {
            $interval_value = 1;
        }


        $stmt = "INSERT INTO repeatable_ticket_templates 
        (created_by, title, description, department, room, location, cc, client, phone_number, request_type, interval_type, interval_value, next_run_date, active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $insert_stmt = mysqli_prepare(HelpDB::get(), $stmt);

        mysqli_stmt_bind_param(
            $insert_stmt,
            "issssssssssis",
            $user_id,
            $title,
            $description,
            $department,
            $room,
            $location,
            $cc,
            $client,
            $phone_number,
            $request_type,
            $interval_type,
            $interval_value,
            $next_run_date
        );
        if (mysqli_stmt_execute($insert_stmt)) {
            $status_alert_type = "success";
            $status_alert_message = "Repeat ticket template added!";
        } else {
            log_app(LOG_ERR, "Error adding repeat ticket template: " . mysqli_stmt_error($insert_stmt) . " User: $_SESSION[username]");
            $status_alert_type = "danger";
            $status_alert_message = "Error adding template: " . mysqli_stmt_error($insert_stmt);
        }
        mysqli_stmt_close($insert_stmt);
        // Redirect to avoid resubmission
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } elseif ($action === 'delete_repeat_ticket') {

        $ticket_id = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);

        try {
            if ($ticket_id) {
                $stmt = "DELETE FROM repeatable_ticket_templates WHERE id = ?";
                $delete_stmt = mysqli_prepare(HelpDB::get(), $stmt);
                if (!$delete_stmt) {
                    throw new Exception("Prepare failed: " . mysqli_error(HelpDB::get()));
                }
                mysqli_stmt_bind_param($delete_stmt, "i", $ticket_id);
                if (!mysqli_stmt_execute($delete_stmt)) {
                    log_app(LOG_ERR, "Error deleting repeat ticket template: " . mysqli_stmt_error($delete_stmt) . " User: $_SESSION[username]");
                    throw new Exception("Execute failed: " . mysqli_stmt_error($delete_stmt));
                }
                mysqli_stmt_close($delete_stmt);
                $status_alert_type = "success";
                $status_alert_message = "Repeat ticket template deleted!";
            } else {
                log_app(LOG_ERR, "Invalid ticket ID provided for deletion. User: $_SESSION[username]");
                $status_alert_type = "danger";
                $status_alert_message = "Invalid ticket ID.";
            }
        } catch (Exception $e) {
            log_app(LOG_ERR, "Error deleting repeat ticket template: " . $e->getMessage());
            $status_alert_type = "danger";
            $status_alert_message = "Error deleting template.";
        }

        // Redirect to avoid resubmission
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}




// Fetch current repeat tickets for this user
$current_tickets = [];
$query = "SELECT * FROM repeatable_ticket_templates WHERE created_by = ? ORDER BY next_run_date ASC";
if ($stmt = mysqli_prepare(HelpDB::get(), $query)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $current_tickets[] = $row;
    }
    mysqli_stmt_close($stmt);
}



// Render View of Page
echo $twig->render('manage_repeat_tickets.twig', [
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

    // page variables
    'depts' => $depts,

    'locations' => $locations,
    'requestTypes' => $requestTypes,
    'current_tickets' => $current_tickets,
]);
