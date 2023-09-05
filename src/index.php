<?php
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
    header('Location: home.php');
    exit();
}

//include local database connection in the variable $database
require_once('includes/helpdbconnect.php');

// Check if login form is submitted
if (isset($_POST['username']) && isset($_POST['password'])) {
    //sanatize inputs
    $input_username = htmlspecialchars(trim($_POST['username']));
    $input_password = htmlspecialchars(trim($_POST['password']));
    if ($ldap_conn) {
        // Bind to LDAP server
        $ldap_bind = @ldap_bind($ldap_conn, 'psd\\' . $input_username, $input_password);

        if ($ldap_bind) {
            // assign username to session
            $_SESSION['username'] = $input_username;

            //establish connection with the vault
            require_once('includes/vaultdbconnect.php');

            //Query User information from the vault DB
            $vault_query = "SELECT * FROM staff_temp WHERE Email='" . $input_username . "@provo.edu'";
            $result = mysqli_query($user_db, $vault_query);

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
                );

                // Store user's permissions in the session
                $_SESSION['permissions'] = $permissions;

                // Update login timestamp
                $update_query = "UPDATE users SET last_login = NOW() WHERE email = '" . $user_data['Email'] . "'";
                $update_result = mysqli_query($database, $update_query);

                if (!$update_result) {
                    echo 'Update query error: ' . mysqli_error($database);
                }
            }
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

function userExistsLocally($email, $database)
{
    $check_query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($database, $check_query);

    // If a row is returned, the user exists
    return mysqli_num_rows($result) > 0;
}

?>
<?php include("includes/header.php"); ?>

<div id="loginWrapper">
    <h1>Login for Help</h1>
    <script>
        function sanitizeInput(input) {
            // Replace potentially dangerous characters with their HTML entities
            return input.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function validateForm() {
            var username = document.forms["loginForm"]["username"].value;
            var password = document.forms["loginForm"]["password"].value;

            // Sanitize the username and password
            var sanitizedUsername = sanitizeInput(username);
            var sanitizedPassword = sanitizeInput(password);

            // Update the form fields with the sanitized values
            document.forms["loginForm"]["username"].value = sanitizedUsername;
            document.forms["loginForm"]["password"].value = sanitizedPassword;

            // Validate the username
            if (username === "") {
                alert("Username must be filled out");
                return false;
            }

            // Validate the password
            if (password === "") {
                alert("Password must be filled out");
                return false;
            }
            return true; // Form is valid
        }
    </script>
    <form id="loginForm" method="POST" onsubmit="return validateForm();">

        <label>Username:</label>
        <input type="text" name="username">

        <label>Password:</label>
        <input type="password" name="password">

        <input type="submit" value="Login">
    </form>
</div>

<?php include("includes/footer.php"); ?>