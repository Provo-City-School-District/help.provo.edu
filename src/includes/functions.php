<?php
// handle 500 error
register_shutdown_function("handleFatalError");

function handleFatalError() {
    $error = error_get_last();
    if ($error && $error['type'] === E_ERROR) {
        // Redirect to your custom 500 error page
        header('Location: /errors/500.html');
        exit;
    }
}

//Checks current page in $_SERVER variable.
function endsWith($haystack, $needle)
{
    return substr($haystack, -strlen($needle)) === $needle;
}

//limit characters in a string
function limitChars($string, $limit)
{
    if (strlen($string) > $limit) {
        $string = substr($string, 0, $limit) . '...';
    }
    return $string;
}
//simple validator that can be used for user input
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }


function log_app(int $priority, string $message)
{
    openlog("appLog", LOG_PID | LOG_PERROR, LOG_LOCAL0);
    syslog($priority, $message);
    closelog();
}

function add_ticket_msg_id_mapping(string $message_id, int $ticket_id)
{
    global $database;

    if (isset($message_id)) {
        $insert_email_id_query = "INSERT INTO ticket_email_ids (email_id, ticket_id) VALUES (?, ?)";
        $insert_email_id_stmt = mysqli_prepare($database, $insert_email_id_query);

        mysqli_stmt_bind_param($insert_email_id_stmt, "si", $message_id, $ticket_id);
        mysqli_stmt_execute($insert_email_id_stmt);
        mysqli_stmt_close($insert_email_id_stmt);
        log_app(LOG_INFO, "Added $message_id to list with ticket id $ticket_id");
        return true;
    } else {
        log_app(LOG_ERR, "message_id was null. Not adding $ticket_id to ticket_email_ids");
        return false;
    }
}