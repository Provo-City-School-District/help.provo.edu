<?php
require_once 'helpdbconnect.php';
session_start();

HelpDB::get()->execute_query('UPDATE users SET remember_me_token = NULL WHERE id = ?', [$_SESSION["user_id"]]);
 
// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

setcookie("COOKIE_REMEMBER_ME", "", time() - 3600, "/");
session_unset();
// Finally, destroy the session.
session_destroy();
header('Location: /index.php');
exit();
