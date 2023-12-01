<?php
if (!session_id()) {
    session_start();
}

// check if logged in. redirects to login page if not
if (!$_SESSION['username']) {
    header('Location:' . getenv('ROOTDOMAIN'));
    exit;
}

?>