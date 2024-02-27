<?php
require_once("status_popup.php");
session_start();
// session_regenerate_id(true);

// Check if user is already logged in
if (isset($_SESSION['username'])) {
    header('Location: tickets.php');
    exit();
}
?>

<?php
// Display Front End
include("header.php");
// Display Status Popup
if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}

?>
<div class="welcomeMessage">Welcome to The Provo School District Help Desk</div>
<div id="loginWrapper">
    <h1>Login for Help</h1>

    <a href="google_login.php" class="button googSSO">Login with Google</a>

</div>

<?php include("footer.php"); ?>