<?php
require from_root("/../vendor/autoload.php");
require_once('helpdbconnect.php');
require_once('functions.php');
require_once('ticket_utils.php');

$feedback_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback_id = filter_input(INPUT_POST, 'feedback_id', FILTER_SANITIZE_SPECIAL_CHARS);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_SPECIAL_CHARS);
    $comments = filter_input(INPUT_POST, 'comments', FILTER_SANITIZE_SPECIAL_CHARS);
    $ticket_id = filter_input(INPUT_POST, 'ticket_id', FILTER_SANITIZE_SPECIAL_CHARS);
    $client_name = filter_input(INPUT_POST, 'client', FILTER_SANITIZE_SPECIAL_CHARS);

    // Store the feedback in the database
    $insert_query = "INSERT INTO help.feedback (feedback_id,ticket_id, rating, comments,client) VALUES (? ,? , ?, ?, ?)";
    $insert_stmt = HelpDB::get()->prepare($insert_query);
    $insert_stmt->bind_param('siiss', $feedback_id, $ticket_id, $rating, $comments, $client_name);
    $insert_stmt->execute();
    log_app(LOG_INFO, "Inserting feedback for ticket $feedback_id");
    echo "Thank you for your feedback!";
    echo "<script>
    setTimeout(function() {
        window.location.href = '/';
    }, 3000);
  </script>";
    exit;
}

if (!$feedback_id) {
    echo "Invalid feedback ID.";
    exit;
}

// Retrieve the ticket information using the feedback ID
$ticket_query = "SELECT * FROM help.tickets WHERE feedback_id = ?";
$ticket_results = HelpDB::get()->execute_query($ticket_query, [$feedback_id]);
$ticket = $ticket_results->fetch_assoc();
if (!$ticket_results) {
    echo "Invalid feedback ID.";
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
    // 'ticket_data' => $ticket,
    'ticket_id' => $ticket['id'],
    'ticket_description' => $ticket_description,
    'ticket_tech' => $ticket_tech,
    'ticket_location' => $ticket_location,
    'ticket_department' => $ticket_department,
    'ticket_subject' => $ticket_subject,
    'feedback_id' => $feedback_id,
    'client_name' => $client_name,
    'recent_notes' => $recent_notes,
]);
