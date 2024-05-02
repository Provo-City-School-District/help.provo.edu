<?php
require "block_file.php";
require "ticket_utils.php";
require "helpdbconnect.php";
require "authentication_utils.php";

if (!$_SESSION["permissions"]["is_admin"]) {
	echo "You do not have permission to view this page";
	exit;
}

$username = $_SESSION["username"];

function test_ticket_creation(int &$ticket_id)
{
	global $database;
	global $username;

	// Test creating ticket
	$ticket_id = 0;
	create_ticket($username, "Ticket Test", "Help", "", 408, $ticket_id);

	assert($ticket_id !== 0);
	assert(client_for_ticket($ticket_id) == $username);

	// Test reading from ticket
	$res = $database->execute_query("SELECT * FROM help.tickets WHERE id = ?", [$ticket_id]);

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
	global $database;
	global $username;


	// Add a note on the ticket
	$res = create_note($ticket_id, $username, "Note test", 1, 32, 0, 14, true);
	assert($res);

	// Test reading from note
	$res = $database->execute_query("SELECT * FROM help.notes WHERE linked_id = ?", [$ticket_id]);

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