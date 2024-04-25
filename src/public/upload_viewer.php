<?php
require from_root("/../vendor/autoload.php");
require "ticket_utils.php";

if (!session_id())
    session_start();

$loader = new \Twig\Loader\FilesystemLoader(from_root('/views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/twig-cache')
]);


echo $twig->render('upload_viewer.twig', [

]);
