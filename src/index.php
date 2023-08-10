<!DOCTYPE html>
<html>
<head>
    <title>PHP-FPM Docker Test</title>
</head>
<body>
    <h1>Hello, PHP-FPM Docker!</h1>
    <?php
// Define LDAP Server
$ldap_host = "ldap://158.91.5.221";
$ldap_port = 389;
$ldap_conn = ldap_connect($ldap_host, $ldap_port);
if (!$ldap_conn) {
    die('Could not connect to LDAP server');
}



// Check if login form is submitted
if (isset($_POST['username']) && isset($_POST['password'])) {
    // Attempt LDAP authentication

    // ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    // ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

    // $ldap_bind = @ldap_bind($ldap_conn, 'psd\\' . $_POST['username'], $_POST['password']);
    // if ($ldap_bind) {
    //     include("includes/helpdbconnect.php");
    //     include("includes/userdb.php");

    //     $query_str = "SELECT * FROM staff_temp WHERE email='". $_POST['username']. "@provo.edu'";
    //     $result = mysqli_query($user_db, $query_str);
    //     $result = $result->fetch_assoc();

    //     $query = $database->prepare("SELECT * FROM users WHERE user='". $_POST['username']. "'");
    //     $query->execute();
    //     $exists = !empty($query->fetch(PDO::FETCH_ASSOC));

    //     if (!$exists) {
    //         // Create their user.
    //         $query = $database->prepare("INSERT INTO users (user, `name`, email) VALUES (?, ?, ?)");
    //         $query->execute([$_POST['username'], $result['firstname']. " ". $result['lastname'], $result['Email']]);
    //     }
        
    //     $remember = md5(uniqid(mt_rand(),true));
    //     $query = $database->prepare("INSERT INTO active_sessions (session_id, user) VALUES (?, ?)");
    //     $query->execute([$remember, $_POST['username']]);
        
    //     $_SESSION['user'] = $_POST['username'];
    //     $_SESSION['session_id'] = $remember;
    //     header('Location: /home.php');
    //     exit();
    // } else {
    //     // If authentication fails, display error message
    //     $error_msg = 'Invalid username or password';
    // }


    if ($ldap_conn) {
        // Bind to LDAP server
        $ldap_bind = @ldap_bind($ldap_conn, 'psd\\' . $_POST['username'], $_POST['password']);
    
        if ($ldap_bind) {
            // User is authenticated
            // echo 'User authenticated';
            header('Location: home.php');
        } else {
            // Authentication failed
            echo 'Authentication failed';
        }
    
        // Close LDAP connection
        ldap_close($ldap_conn);
    } else {
        // Failed to connect to LDAP server
        echo 'Failed to connect to LDAP server';
    }



}
    ?>
<?php include("includes/header.php"); ?>
<div id="loginWrapper">
    <h1>Login for Help</h1>
    <?php if (isset($error_msg)) {
        echo '<p>' . $error_msg . '</p>';
    } ?>
    <form id="loginForm" method="POST">
        <label>Username:</label>
        <input type="text" name="username" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <input type="submit" value="Login">
    </form>
</div>


<?php include("includes/footer.php"); ?>