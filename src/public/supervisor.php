<?php
include "header.php";
require_once "tickets_template.php";
require "status_popup.php";

if ($_SESSION['permissions']['is_supervisor'] != 1) {
    // User is not an admin
    echo 'You do not have permission to view this page.';
    exit;
}

require_once('helpdbconnect.php');
require_once("ticket_utils.php");

$department = $_SESSION['department'] ?? null;
$sitenumber = get_sitenumber_from_location_id($department);


function process_query_result($query_result, $label_field)
{
    $count = [];

    while ($row = mysqli_fetch_assoc($query_result)) {
        $label = $row[$label_field];
        if ($label == null || $label == "")
            $label = "unassigned";

        if (!isset($count[$label]))
            $count[$label] = 1;
        else
            $count[$label]++;
    }

    asort($count);

    $processedData = [];
    foreach ($count as $name => $count) {
        $processedData[] = array("y" => $count, "label" => $name);
    }

    return $processedData;
}


// process the data for admin report charts
function process_query_result_wlinks($query_result, $label_field, $id_field, $url_field)
{
    $count = [];
    $ids = [];

    while ($row = mysqli_fetch_assoc($query_result)) {
        $label = $row[$label_field];
        $id = $row[$id_field];
        if ($label == null || $label == "")
            $label = "unassigned";

        if (!isset($count[$label])) {
            $count[$label] = 1;
            $ids[$label] = $id;
        } else {
            $count[$label]++;
        }
    }

    asort($count);

    $processedData = [];
    foreach ($count as $name => $count) {
        $url = $url_field . urlencode($ids[$name]);
        $processedData[] = array("y" => $count, "label" => $name, "url" => $url);
    }

    return $processedData;
}



// Check if an error message is set
if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}


// Query open tickets based on tech
$tech_query = <<<STR
    SELECT t.employee, u.id 
    FROM tickets t 
    JOIN users u ON t.employee = u.username 
    JOIN user_settings us ON u.id = us.user_id
    WHERE t.status NOT IN ('closed', 'resolved') 
    AND us.department = ?
STR;
$tech_query_result = HelpDB::get()->execute_query($tech_query, [$department]);
$url_path_techs = "/controllers/users/manage_user.php?id=";
$allTechs = process_query_result_wlinks($tech_query_result, "employee", "id", $url_path_techs);

// Query open tickets based on location:

$location_query = <<<STR
    SELECT locations.location_name, tickets.location
    FROM tickets 
    INNER JOIN locations ON tickets.location = locations.sitenumber 
    WHERE tickets.status NOT IN ('closed', 'resolved')
    AND locations.is_archived = 0
STR;

$location_query_result = HelpDB::get()->execute_query($location_query);
$allLocations = process_query_result($location_query_result, "location_name");

// Query open tickets based on field tech:
// $field_tech_query = <<<STR
//     SELECT t.employee 
//     FROM tickets t
//     INNER JOIN users u ON t.employee = u.username 
//     INNER JOIN user_settings us ON u.id = us.user_id
//     WHERE t.status NOT IN ('closed', 'resolved') AND us.is_tech = 1
// STR;

// $field_tech_query_result = HelpDB::get()->execute_query($field_tech_query);
// $fieldTechs = process_query_result($field_tech_query_result, "employee");

?>
<h1>Supervisor</h1>
<form>
    <label for="refreshInterval">Select refresh interval:</label>
    <select id="refreshInterval" name="refreshInterval">
        <option value="0">No refresh</option>
        <option value="10000">10 Second</option>
        <option value="30000">30 Seconds</option>
        <option value="60000">60 Seconds</option>
        <option value="300000">5 Minutes</option>
        <option value="600000">10 Minutes</option>
        <option value="3600000">60 Minutes</option>
    </select>
</form>
<h2>Reports</h2>
<div class="grid2 canvasjsreport">
    <div id="techOpenTicket" style="height: 370px; width: 100%;"></div>
    <div id="byLocation" style="height: 370px; width: 100%;"></div>
    <!-- <div id="fieldTechOpen" style="height: 370px; width: 100%;"></div> -->
</div>






<h2>Unassigned Tickets</h2>

<?php

//query for unassigned tickets for location
$unassigned_ticket_query = <<<unassigned_tickets
SELECT *
FROM tickets
WHERE status NOT IN ('closed', 'resolved') 
AND (employee IS NULL OR employee = 'unassigned' OR employee = '')
AND department = ?
GROUP BY id
unassigned_tickets;

$ticket_result = HelpDB::get()->execute_query($unassigned_ticket_query, [$sitenumber]);
display_tickets_table($ticket_result, HelpDB::get());


?>
<script src="/includes/js/charts.js?v=0.1.0" type="text/javascript"></script>
<script>
    let allTechs = <?php echo json_encode($allTechs, JSON_NUMERIC_CHECK); ?>;
    let byLocation = <?php echo json_encode($allLocations, JSON_NUMERIC_CHECK); ?>;
    // let fieldTechOpen = <?php //echo json_encode($fieldTechs, JSON_NUMERIC_CHECK); 
                            ?>;
</script>
<script type="text/javascript">
    // Function to reload the page at the specified interval
    function autoRefresh(interval) {
        clearInterval(window.refreshIntervalId);
        if (interval > 0) {
            window.refreshIntervalId = setInterval(function() {
                location.reload();
            }, interval);
        }
    }

    // Event listener for change in the auto refresh dropdown
    document.getElementById('refreshInterval').addEventListener('change', function() {
        let interval = this.value;
        // Store the selected interval in session storage
        sessionStorage.setItem('refreshInterval', interval);
        autoRefresh(interval);
    });

    // Event listener for DOMContentLoaded event to start the auto-refresh
    document.addEventListener('DOMContentLoaded', function() {
        // Retrieve the selected interval from session storage
        let interval = sessionStorage.getItem('refreshInterval');
        if (interval) {
            // Set the dropdown to the stored value
            document.getElementById('refreshInterval').value = interval;
            autoRefresh(interval);
        }
    })
</script>
<?php include("footer.php"); ?>
<script src="/includes/js/external/canvasjs.min.js"></script>