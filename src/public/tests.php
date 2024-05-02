<?php
require "block_file.php";
require "ticket_utils.php";
require "helpdbconnect.php";

$username = $_SESSION["username"];

function test_ticket_creation(int &$ticket_id)
{
	global $database;
	global $username;

	// Test creating ticket
	$ticket_id = 0;
	create_ticket($username, "Ticket Test", "Help", "", 408, $ticket_id);

	assert($ticket_id !== 0);

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

$ticket_id = 0;
if (test_ticket_creation($ticket_id)) {
	echo "Ticket creation: passed<br>";
}

if (test_note_creation($ticket_id)) {
	echo "Note creation: passed<br>";
}