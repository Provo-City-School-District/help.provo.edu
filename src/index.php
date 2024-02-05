require_once("status_popup.php");
session_start();
session_regenerate_id(true);
// Display Status Popup
if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}
<div id="loginWrapper">
    <h1>Login for Help</h1>

    <a href="google_login.php" class="button googSSO">Login with Google</a>

</div>

<?php include("footer.php"); ?>