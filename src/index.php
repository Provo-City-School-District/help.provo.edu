<?php
require_once("status_popup.php");

// Define LDAP Server
$ldap_host = getenv('LDAPHOST');
$ldap_port = getenv('LDAPPORT');
$ldap_conn = ldap_connect($ldap_host, $ldap_port);
if (!$ldap_conn) {
    die('Could not connect to LDAP server');
}
session_start();

// Check if user is already logged in
if (isset($_SESSION['username'])) {
    header('Location: tickets.php');
    exit();
}

//include local database connection in the variable $database
require_once('helpdbconnect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST')  {
    $input_username = htmlspecialchars(trim($_POST['username']));
    $input_password = htmlspecialchars(trim($_POST['password']));
    if ($ldap_conn) {
        // Bind to LDAP server
        $ldap_bind = ldap_bind($ldap_conn, 'psd\\' . $input_username, $input_password);

        // check that inputs are not empty, otherwise an anonymous LDAP bind can get through
        $inputs_valid = !(empty($input_username) || empty($input_password));

        if ($ldap_bind && $inputs_valid) {
            // assign username to session
            $_SESSION['username'] = $input_username;

            //establish connection with the vault
            require_once('vaultdbconnect.php');

            //Query User information from the vault DB
            $vault_query = "SELECT * FROM staff_temp WHERE Email='" . $input_username . "@provo.edu'";
            $result = mysqli_query($vault_db, $vault_query);

            if ($result) {
                //Fetch User Data
                $user_data = mysqli_fetch_assoc($result);

                //check if user already exists in local database
                //if not, insert their information into the local database
                if (!userExistsLocally($user_data['Email'], $database)) {

                    // Insert user data into the local database
                    $insert_query = "INSERT INTO users (username, email, lastname, firstname, ifasid, worksite, pre_name) VALUES ('" . $input_username . "', '" . $user_data['Email'] . "', '" . $user_data['lastname'] . "', '" . $user_data['firstname'] . "', '" . $user_data['ifasid'] . "', '" . $user_data['worksite'] . "', '" . $user_data['pre_name'] . "')";

                    // mysqli_query($database, $insert_query);
                    $insert_result = mysqli_query($database, $insert_query);
                    if (!$insert_result) {
                        echo 'Insert query error: ' . mysqli_error($database);
                    }
                }

                // Update login timestamp
                $local_user_query = "SELECT * FROM users WHERE username = '" . $_SESSION['username'] . "'";
                $local_query_results = mysqli_query($database, $local_user_query);
                $local_query_data = mysqli_fetch_assoc($local_query_results);

                // Retrieve user's permissions from the users table
                $permissions = array(
                    'can_view_tickets' => $local_query_data['can_view_tickets'],
                    'can_create_tickets' => $local_query_data['can_create_tickets'],
                    'can_edit_tickets' => $local_query_data['can_edit_tickets'],
                    'can_delete_tickets' => $local_query_data['can_delete_tickets'],
                    'is_admin' => $local_query_data['is_admin'],
                    'is_tech' => $local_query_data['is_tech'],
                    
                );
                // Set color scheme
                $_SESSION['color_scheme'] = $local_query_data['color_scheme'];

                // Store user's permissions in the session
                $_SESSION['permissions'] = $permissions;

                // Update login timestamp
                $update_query = "UPDATE users SET last_login = NOW() WHERE email = '" . $user_data['Email'] . "'";
                $update_result = mysqli_query($database, $update_query);

                if (!$update_result) {
                    echo 'Update query error: ' . mysqli_error($database);
                }
            }
            $loginMessage = "Successful login for username: " .  $input_username . " IP: " . $_SERVER["REMOTE_ADDR"] . " at " . date("Y-m-d H:i:s") . "\n";
            error_log($loginMessage, 0);
            header('Location: tickets.php');
        } else {
            // Authentication failed
            $_SESSION['current_status'] = 'Authentication failed';
            $_SESSION['status_type'] = 'error';
            
            // Log the failed login attempt
            $logMessage = "Failed login attempt for username: " .  $input_username . " IP: " . $_SERVER["REMOTE_ADDR"] . " at " . date("Y-m-d H:i:s") . "\n";
            error_log($logMessage, 0);

            // Clear the username and password to prevent resubmission
            unset($_POST["password"]);
            unset($_POST["username"]);
        }

        // Close LDAP connection
        ldap_close($ldap_conn);
    } else {
        // Failed to connect to LDAP server
        $_SESSION['current_status'] = 'Failed to connect to LDAP server';
        $_SESSION['status_type'] = 'error';
        $failedLDAP = "failed to connect to LDAP server";
        error_log($failedLDAP, 0);
    }
}

function userExistsLocally($email, $database)
{
    $check_query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($database, $check_query);

    // If a row is returned, the user exists
    return mysqli_num_rows($result) > 0;
}

?>
<?php include("header.php");

if (isset($_SESSION['current_status'])) {
    $status_popup = new StatusPopup($_SESSION["current_status"], StatusPopupType::fromString($_SESSION["status_type"]));
    echo $status_popup;

    unset($_SESSION['current_status']);
    unset($_SESSION['status_type']);
}
?>

<div id="loginWrapper">
    <h1>Login for Help</h1>
    <form id="loginForm" method="POST">

        <label>Username:</label>
        <input type="text" name="username">

        <label>Password:</label>
        <input type="password" name="password">

        <input type="submit" value="Login">
    </form>
</div>

<?php include("footer.php"); ?>