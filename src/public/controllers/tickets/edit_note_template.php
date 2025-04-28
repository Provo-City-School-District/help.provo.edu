<?php
require 'block_file.php';
require 'ticket_utils.php';
require from_root("/../vendor/autoload.php");
require from_root("/new-controllers/base_variables.php");

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
	'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);


$user_id = get_id_for_user($_SESSION['username']);
$name = $_GET['name'] ?? '';

if (!$name) {
    header("Location: manage_note_templates.php");
    exit;
}

// Fetch the template
$stmt = HelpDB::get()->execute_query(
    "SELECT content FROM note_templates WHERE user_id = ? AND name = ?",
    [$user_id, $name]
);
$template = $stmt->fetch_assoc();


echo $twig->render('edit_note_template.twig', [
    'name' => $name,
    'content' => $template['content']
]);
