<?php

// Get the user agent
$user_agent = $_SERVER['HTTP_USER_AGENT'];

// Check if the user agent is from InterMapper
if (strpos($user_agent, 'InterMapper') === false && !session_id()) {
    session_start();
}

// Regenerate session ID and delete the old session
if (session_id()) {
    session_regenerate_id(true);
}

// Ensure $_SESSION is an array
if (!isset($_SESSION)) {
    $_SESSION = [];
}

include_once('functions.php');
include_once('helpdbconnect.php');
