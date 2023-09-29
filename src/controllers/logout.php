<?php
session_start();

// Remove their session from the database
// if (!isset($_GET['soft'])) {
//     // If it is a hard logout, remove every session they have.
//     include_once("../includes/helpdbconnect.php");

//     $query = $database->prepare("DELETE FROM active_sessions WHERE user=?");
//     $query->execute([$_SESSION['user']]);
// } else {
//     // This is a soft logout, which means they want their data, so only clear old sessions not just this one.
//     include_once("../includes/helpdbconnect.php");

//     $query = $database->prepare("DELETE FROM active_sessions WHERE user=? AND session_id NOT IN ( ? )");
//     $query->execute([$_SESSION['user'], $_SESSION['session_id']]);
// }

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_unset();
// Finally, destroy the session.
session_destroy();
header('Location: /index.php');
exit();
