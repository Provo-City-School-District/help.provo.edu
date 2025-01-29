<?php
require_once "helpdbconnect.php";
require_once "block_file.php";
require_once "ticket_utils.php";
require_once "authentication_utils.php";

if (!$_SESSION["permissions"]["is_admin"]) {
    echo "You do not have permission to view this page";
    exit;
}

$username = $_SESSION["username"];

function test_ticket_creation(int &$ticket_id)
{
    global $username;

    // Test creating ticket
    $ticket_id = 0;
    create_ticket($username, "Ticket Test", "Help", "", 408, $ticket_id);

    assert($ticket_id !== 0);
    assert(client_for_ticket($ticket_id) == $username);

    // Test reading from ticket
    $res = HelpDB::get()->execute_query("SELECT * FROM help.tickets WHERE id = ?", [$ticket_id]);

    assert($res);
    assert($res->num_rows === 1);

    while ($row = $res->fetch_assoc()) {
        assert($row["name"] === "Ticket Test");
        assert($row["description"] === "Help");
    }

    return true;
}

function test_note_creation(int $ticket_id)
{
    global $username;

    // Add a note on the ticket
    $res = create_note($ticket_id, $username, "Note test", 1, 32, 0, 14, true);
    assert($res);

    // Test reading from note
    $res = HelpDB::get()->execute_query("SELECT * FROM help.notes WHERE linked_id = ?", [$ticket_id]);

    assert($res);
    assert($res->num_rows === 1);

    while ($row = $res->fetch_assoc()) {
        assert($row["creator"] === $username);
        assert($row["note"] === "Note test");
        assert($row["work_hours"] === 1);
        assert($row["work_minutes"] === 32);
        assert($row["travel_hours"] === 0);
        assert($row["travel_minutes"] === 14);
    }
    return true;
}

function test_helper_methods()
{
    global $username;

    $email_test_str = "peterb@provo.edu";
    assert(email_if_valid($email_test_str) == $email_test_str);

    $split_email_test_str = "peterb@provo.edu,test1@provo.edu,test2@provo.edu";
    $arr = split_email_string_to_arr($split_email_test_str);

    assert($arr[0] == "peterb@provo.edu");
    assert($arr[1] == "test1@provo.edu");
    assert($arr[2] == "test2@provo.edu");

    $location_name = location_name_from_id(408);
    assert($location_name == "Dixon");

    $location_name = location_name_from_id(1896);
    assert($location_name == "Aux Services");

    $location_name = location_name_from_id(555);
    assert($location_name == "Slate Canyon");

    assert(user_exists_locally($username));

    assert(get_id_for_user("braxtona") == 5);

    return true;
}

function test_pages()
{
    $pages = [
        "admin.php",
        "google_login.php",
        "index.php",
        "note_shortcuts.php",
        "profile.php",
        "supervisor.php",
        "tickets.php",
        "upload_viewer.php",

        "controllers/admin/delete_exclude_day.php",
        "controllers/admin/exclude_days.php",

        "controllers/tickets/add_note_handler.php",
        "controllers/tickets/add_task_handler.php",
        "controllers/tickets/alert_delete.php",
        "controllers/tickets/archived_ticket_view.php",
        "controllers/tickets/create_ticket.php",
        "controllers/tickets/delete_note.php",
        "controllers/tickets/edit_note.php",
        "controllers/tickets/edit_ticket.php",
        "controllers/tickets/flagged_tickets.php",
        "controllers/tickets/insert_ticket.php",
        "controllers/tickets/location_tickets.php",
        "controllers/tickets/merge_tickets_handler.php",
        "controllers/tickets/recent_tickets.php",
        "controllers/tickets/search_archived_query_builder.php",
        "controllers/tickets/search_query_builder.php",
        "controllers/tickets/search_tickets.php",
        "controllers/tickets/subordinate_tickets.php",
        "controllers/tickets/ticket_history.php",
        "controllers/tickets/update_ticket.php",
        "controllers/tickets/upload_files_handler.php",

        "controllers/users/manage_user.php",
        "controllers/users/update_user_settings.php",
        "controllers/users/update_user.php"
    ];

    echo "Testing pages\n<ul>";
    foreach ($pages as $url) {
        $page_url = "http://localhost/$url";
        $curl = curl_init($page_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        $data = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        echo "<li>$page_url - $http_status</li>";
        assert($http_status == 200 || $http_status == 302);
    }

    echo "</ul>";
    return true;
}

$ticket_id = 0;
if (test_ticket_creation($ticket_id)) {
    echo "Ticket creation: passed<br>";
}

if (test_note_creation($ticket_id)) {
    echo "Note creation: passed<br>";
}

if (test_helper_methods()) {
    echo "Helper methods: passed<br>";
}
echo "<br>Tests complete";