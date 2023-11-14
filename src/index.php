<?php
require_once("status_popup.php");
require("functions.php");
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $ldap_dn = getenv('LDAP_DN');
            $ldap_user = getenv('LDAP_USER');
            $ldap_password = getenv('LDAP_PASS');
            if (!$ldap_bind) {
                die('Could not bind to LDAP server.');
            }
            // Search LDAP directory
            $search = '(&(objectCategory=person)(objectClass=user)(sAMAccountName=' . $input_username . '))'; // Your search filter
            $ldap_result = ldap_search($ldap_conn, $ldap_dn, $search);
            if (!$ldap_result) {
                die('LDAP search failed.');
            }
            // Get entries from search result
            $ldap_result = ldap_get_entries($ldap_conn, $ldap_result);
  
            if ($ldap_result) {

                // Loop through results
                for ($i = 0; $i < $ldap_result['count']; $i++) {
                    $username = $ldap_result[$i]['samaccountname'][0];
                    $firstname = $ldap_result[$i]['givenname'][0];
                    $lastname = $ldap_result[$i]['sn'][0];
                    $email = $ldap_result[$i]['mail'][0];
                    $employee_id = $ldap_result[$i]['employeeid'][0];
                }
                //check if user already exists in local database
                //if not, insert their information into the local database
                if (!user_exists_locally($email)) {

                    // Insert user data into the local database
                    $insert_query = "INSERT INTO users (username, email, lastname, firstname, ifasid) VALUES ('" . $input_username . "', '" . $email . "', '" . $lastname . "', '" . $firstname . "', '" . $employee_id . "')";

                    //mysqli_query($database, $insert_query);
                    $insert_result = mysqli_query($database, $insert_query);
                    if (!$insert_result) {
                        echo 'Insert query error: ' . mysqli_error($database);
                    }
                }

                // Query Local user information
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
                    'is_field_tech' => $local_query_data['is_field_tech'],

                );
                // Set color scheme
                $_SESSION['color_scheme'] = $local_query_data['color_scheme'];

                // Store user's permissions in the session
                $_SESSION['permissions'] = $permissions;

                // Update login timestamp
                $update_query = "UPDATE users SET last_login = NOW() WHERE email = '" . $email . "'";
                $update_result = mysqli_query($database, $update_query);

                if (!$update_result) {
                    echo 'Update query error: ' . mysqli_error($database);
                }
            }
            // Log the successful login
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