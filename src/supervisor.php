<?php
include("header.php");
require_once(from_root("/includes/tickets_template.php"));
require("status_popup.php");
if ($_SESSION['permissions']['is_supervisor'] != 1) {
    // User is not an admin
    echo 'You do not have permission to view this page.';
    exit;
}

require_once('helpdbconnect.php');
require("ticket_utils.php");
$managed_location = $_SESSION['permissions']['location_manager_sitenumber'];
// process the data for admin report charts
function process_query_result($query_result, $label_field, $id_field)
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
        $url = "/controllers/users/manage_user.php?id=" . urlencode($ids[$name]);
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
    WHERE t.status NOT IN ('closed', 'resolved')
    STR;
$tech_query_result = mysqli_query($database, $tech_query);
$allTechs = process_query_result($tech_query_result, "employee", "id");

// Query open tickets based on location:
$location_query = <<<STR
    SELECT locations.location_name, tickets.location
    FROM tickets 
    INNER JOIN locations ON tickets.location = locations.sitenumber 
    WHERE tickets.status NOT IN ('closed', 'resolved')
STR;

$location_query_result = mysqli_query($database, $location_query);
// $allLocations = process_query_result($location_query_result, "location_name");

// Query open tickets based on field tech:
$field_tech_query = <<<STR
    SELECT tickets.employee 
    FROM tickets 
    INNER JOIN users ON tickets.employee = users.username 
    WHERE tickets.status NOT IN ('closed', 'resolved') AND users.is_tech = 1
STR;

$field_tech_query_result = mysqli_query($database, $field_tech_query);
// $fieldTechs = process_query_result($field_tech_query_result, "employee");

?>
<h1>Supervisor</h1>
<h2>Reports</h2>
<div class="grid3 canvasjsreport">
    <div id="techOpenTicket" style="height: 370px; width: 100%;"></div>
    <div id="byLocation" style="height: 370px; width: 100%;"></div>
    <div id="fieldTechOpen" style="height: 370px; width: 100%;"></div>
</div>




<?php echo json_encode($allTechs, JSON_NUMERIC_CHECK); ?>

<h2>Unassigned Tickets</h2>

<?php

//query for unassigned tickets for location
$unassigned_ticket_query = <<<unassigned_tickets
SELECT *
FROM tickets
WHERE status NOT IN ('closed', 'resolved') 
AND (employee IS NULL OR employee = 'unassigned')
unassigned_tickets;


$ticket_result = mysqli_query($database, $unassigned_ticket_query);
display_tickets_table($ticket_result, $database);
?>
<script src="/includes/js/charts.js?v=0.1.0" type="text/javascript"></script>
<script>
    let allTechs = <?php echo json_encode($allTechs, JSON_NUMERIC_CHECK); ?>;
    let byLocation = <?php echo json_encode($allLocations, JSON_NUMERIC_CHECK); ?>;
    let fieldTechOpen = <?php echo json_encode($fieldTechs, JSON_NUMERIC_CHECK); ?>;
</script>
<?php include("footer.php"); ?>