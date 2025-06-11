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
$department_name = location_name_from_id(get_sitenumber_from_location_id($department)) ?? "Unknown Department";

// helper function to process query results for admin report charts
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
    INNER JOIN users ON tickets.employee = users.username
    INNER JOIN user_settings ON users.id = user_settings.user_id
    WHERE tickets.status NOT IN ('closed', 'resolved')
    AND locations.is_archived = 0
    AND user_settings.department = ?
STR;

$location_query_result = HelpDB::get()->execute_query($location_query, [$department]);
$allLocations = process_query_result($location_query_result, "location_name");





// Query supervisor alerts for users within the current user's department
$supervisor_alerts_query = <<<alerts
    SELECT a.*
    FROM alerts a
    INNER JOIN users u ON a.employee = u.username
    INNER JOIN user_settings us ON u.id = us.user_id
    WHERE a.supervisor_alert = 1
    AND us.department = ?
alerts;

$supervisor_alerts_result = HelpDB::get()->execute_query($supervisor_alerts_query, [$department]);
$supervisorAlerts = mysqli_fetch_all($supervisor_alerts_result, MYSQLI_ASSOC);





// Query for open tickets count for the current department
$open_tickets_query = <<<SQL
    SELECT COUNT(*) as open_count
    FROM tickets
    WHERE status NOT IN ('closed', 'resolved')
    AND department = ?
SQL;

$open_tickets_result = HelpDB::get()->execute_query($open_tickets_query, [$sitenumber]);
$open_tickets_row = mysqli_fetch_assoc($open_tickets_result);
$open_tickets_count = $open_tickets_row['open_count'];




// Query for new tickets today
$new_tickets_today_query = <<<SQL
    SELECT COUNT(*) as new_count
    FROM tickets
    WHERE DATE(created) = CURDATE()
    AND department = ?
SQL;
$new_tickets_today_result = HelpDB::get()->execute_query($new_tickets_today_query, [$sitenumber]);
$new_tickets_today_row = mysqli_fetch_assoc($new_tickets_today_result);
$new_tickets_today_count = $new_tickets_today_row['new_count'];





// Query for tickets resolved/closed today
$resolved_tickets_today_query = <<<SQL
    SELECT COUNT(*) as resolved_count
    FROM tickets
    WHERE DATE(last_updated) = CURDATE()
    AND status IN ('closed', 'resolved')
    AND department = ?
SQL;
$resolved_tickets_today_result = HelpDB::get()->execute_query($resolved_tickets_today_query, [$sitenumber]);
$resolved_tickets_today_row = mysqli_fetch_assoc($resolved_tickets_today_result);
$resolved_tickets_today_count = $resolved_tickets_today_row['resolved_count'];

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
<div class="grid3 canvasjsreport">

    <div>
        <h3 class="center"><?= $department_name ?> Department Tech Open Tickets</h3>
        <div id="techOpenTicket" style="height: 370px; width: 100%;"></div>
    </div>

    <div>
        <h3 class="center"><?= $department_name ?> Department Open Tickets By Location</h3>
        <div id="byLocation" style="height: 370px; width: 100%;"></div>
    </div>

    <div class="alerts_wrapper">
        <h3 class="center">Quick Stats</h3>
        <p>Current Open Tickets for <?= $department_name ?> : <?= $open_tickets_count ?></p>
        <p>New <?= $department_name ?> Tickets Today: <?= $new_tickets_today_count ?></p>
        <p><?= $department_name ?> Tickets Resolve/Closed Today: <?= $resolved_tickets_today_count ?></p>

        <h3 class="nextToCanvas">Supervisor Alerts</h3>
        <table id="alertsTable" class="display">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Message</th>
                    <th>Assigned To</th>
                    <!-- <th>Alert Level</th> -->
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($supervisorAlerts) != 0) {
                    foreach ($supervisorAlerts as $alert) {
                        echo "<tr>";
                        echo "<td><a href='/controllers/tickets/edit_ticket.php?id=" . $alert['ticket_id'] . "'>" . $alert['ticket_id'] . "</a></td>";
                        echo "<td>" . $alert['message'] . "</td>";
                        echo "<td>" . $alert['employee'] . "</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

</div>





<h2>Unassigned Tickets</h2>
<?php display_tickets_table($ticket_result, HelpDB::get()); ?>






<script src="/includes/js/external/canvasjs.min.js"></script>
<script>
    //================================= Charts =================================
    // Chart for all techs open tickets in the department
    window.addEventListener("load", function() {
        // tech department
        var allTechsChart = new CanvasJS.Chart("techOpenTicket", {
            height: 1000,
            animationEnabled: true,
            axisY: {
                title: "Ticket Count",
                includeZero: true,
                labelFontSize: 14,
            },
            axisX: {
                interval: 1, // Set the interval of the x-axis labels to 1
                labelFontSize: 14,
            },
            data: [{
                type: "bar",
                yValueFormatString: "#,##",
                indexLabel: "{y}",
                indexLabelPlacement: "inside",
                indexLabelFontSize: 1,
                dataPoints: <?php echo json_encode($allTechs, JSON_NUMERIC_CHECK); ?>,
                click: function(e) {
                    window.location.href = e.dataPoint.url;
                },
            }, ],
        });
        allTechsChart.render();

        // Chart for locations of open tickets in the department
        var byLocationChart = new CanvasJS.Chart("byLocation", {
            height: 1000,
            animationEnabled: true,
            axisY: {
                title: "Ticket Count",
                includeZero: true,
                labelFontSize: 14,
            },
            axisX: {
                interval: 1, // Set the interval of the x-axis labels to 1
                labelFontSize: 14,
            },
            data: [{
                type: "bar",
                yValueFormatString: "#,##",
                indexLabel: "{y}",
                indexLabelPlacement: "inside",
                indexLabelFontSize: 1,
                dataPoints: <?php echo json_encode($allLocations, JSON_NUMERIC_CHECK); ?>,
            }, ],
        });
        byLocationChart.render();
    });
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
<script>
    $(document).ready(function() {
        $('#alertsTable').DataTable();
    });
</script>
<?php include("footer.php"); ?>