<?php
// Query the alerts table
$alert_query = "SELECT * FROM alerts WHERE username = '$username'";
$alert_result = mysqli_query($database, $alert_query);
$alerts = mysqli_fetch_all($alert_result, MYSQLI_ASSOC);

// Display the alerts
foreach ($alerts as $alert) {
    echo '<div class="alert" data-id="' . $alert['id'] . '">
        <p>' . $alert['message'] . '</p>
        <button class="clear-alert">Clear</button>
    </div>';
}
?>