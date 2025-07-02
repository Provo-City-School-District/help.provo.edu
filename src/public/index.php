<?php
require_once("status_popup.php");
require_once("init.php");
require_once "authentication_utils.php";

// attempt login
if (!isset($_SESSION['username'])) {
    // attempt login if remember me is set
    if (isset($_COOKIE['COOKIE_REMEMBER_ME'])) {
        log_app(LOG_INFO, "Attempting to login from cookie");
        $login_token = $_COOKIE['COOKIE_REMEMBER_ME'];

        $user_result = HelpDB::get()->execute_query(
            'SELECT id, username FROM users WHERE (remember_me_token = ? AND last_login >= NOW() - INTERVAL 7 DAY)',
            [$login_token]
        );
        // found user, log them in
        if ($user_result->num_rows == 1) {
            session_start();
            $data = $user_result->fetch_assoc();
            $user_id = $data["id"];
            $username = $data["username"];
            login_user($user_id, $username);
        }
    }
}

if (isset($_SESSION['username'])) {
    header('Location: tickets.php');
    exit();
} 

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