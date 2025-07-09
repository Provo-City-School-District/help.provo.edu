<?php
require from_root("/../vendor/autoload.php");
require_once('block_file.php');
require_once('helpdbconnect.php');
require_once('functions.php');
require_once('ticket_utils.php');

$feedback_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);

// Validate feedback ID exists in the database
if ($feedback_id) {
    $feedback_check_query = "SELECT COUNT(*) AS count FROM help.tickets WHERE feedback_id = ?";
    $feedback_check_result = HelpDB::get()->execute_query($feedback_check_query, [$feedback_id]);
    $feedback_check = $feedback_check_result->fetch_assoc();

    if ($feedback_check['count'] == 0) {
        log_app(LOG_ERR, "Invalid feedback ID: $feedback_id");
        echo "An error occurred. Please try again later. If the problem persists, contact support.";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    session_start();
    $csrf_token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!$csrf_token || $csrf_token !== $_SESSION['csrf_token']) {
        log_app(LOG_ERR, "CSRF token validation failed.");
        echo "An error occurred. Please try again later. If the problem persists, contact support.";
        exit;
    }

    $feedback_id = filter_input(INPUT_POST, 'feedback_id', FILTER_SANITIZE_SPECIAL_CHARS);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comments = filter_input(INPUT_POST, 'comments', FILTER_SANITIZE_SPECIAL_CHARS);
    $ticket_id = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);
    $client_name = filter_input(INPUT_POST, 'client', FILTER_SANITIZE_SPECIAL_CHARS);

    // Check if feedback already exists for this ticket
    $feedback_exists_query = "SELECT COUNT(*) AS count FROM help.feedback WHERE feedback_id = ? AND ticket_id = ?";
    $feedback_exists_result = HelpDB::get()->execute_query($feedback_exists_query, [$feedback_id, $ticket_id]);
    $feedback_exists = $feedback_exists_result->fetch_assoc();

    if ($feedback_exists['count'] > 0) {
        log_app(LOG_ERR, "Feedback already submitted for ticket ID: $ticket_id");
        echo "An error occurred. Please try again later. If the problem persists, contact support.";
        exit;
    }

    // Validate ticket ID matches feedback ID
    $ticket_validation_query = "SELECT COUNT(*) AS count FROM help.tickets WHERE id = ? AND feedback_id = ?";
    $ticket_validation_result = HelpDB::get()->execute_query($ticket_validation_query, [$ticket_id, $feedback_id]);
    $ticket_validation = $ticket_validation_result->fetch_assoc();

    if ($ticket_validation['count'] == 0) {
        log_app(LOG_ERR, "Invalid ticket ID: $ticket_id or feedback ID: $feedback_id");
        echo "An error occurred. Please try again later. If the problem persists, contact support.";
        exit;
    }

    // Store the feedback in the database
    $insert_query = "INSERT INTO help.feedback (feedback_id, ticket_id, rating, comments, client) VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = HelpDB::get()->prepare($insert_query);
    $insert_stmt->bind_param('siiss', $feedback_id, $ticket_id, $rating, $comments, $client_name);
    $insert_stmt->execute();
    log_app(LOG_INFO, "Inserting feedback for ticket $feedback_id");
    echo "Thank you for your feedback! You'll be redirected to the homepage in a few seconds.";
    echo "<script>
    setTimeout(function() {
        window.location.href = '/';
    }, 3000);
  </script>";
    exit;
}

// Generate CSRF token for the form
session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Retrieve the ticket information using the feedback ID
$ticket_query = "SELECT * FROM help.tickets WHERE feedback_id = ?";
$ticket_results = HelpDB::get()->execute_query($ticket_query, [$feedback_id]);
$ticket = $ticket_results->fetch_assoc();
if (!$ticket_results) {
    log_app(LOG_ERR, "Failed to retrieve ticket information for feedback ID: $feedback_id");
    echo "An error occurred. Please try again later. If the problem persists, contact support.";
    exit;
}
$get_client_name = get_local_name_for_user($ticket['client']);
$get_tech_name = get_local_name_for_user($ticket['employee']);
$client_name = $get_client_name['firstname'] . " " . $get_client_name['lastname'];
$ticket_tech = $get_tech_name['firstname'] . " " . $get_tech_name['lastname'];

$ticket_description = html_entity_decode($ticket['description']);
$ticket_location = location_name_from_id($ticket['location']);
$ticket_department = location_name_from_id($ticket['department']);
$ticket_subject = $ticket['name'];
$recent_notes = get_ticket_notes($ticket['id'], 3);

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);
echo $twig->render('feedback.twig', [
    // base variables
    'color_scheme' => 'light',
    'current_year' => $current_year,
    'app_version' => $app_version,

    // Page variables
    'ticket_id' => $ticket['id'],
    'ticket_description' => $ticket_description,
    'ticket_tech' => $ticket_tech,
    'ticket_location' => $ticket_location,
    'ticket_department' => $ticket_department,
    'ticket_subject' => $ticket_subject,
    'feedback_id' => $feedback_id,
    'client_name' => $client_name,
    'recent_notes' => $recent_notes,
    'csrf_token' => $_SESSION['csrf_token'], // Pass CSRF token to the form
]);
