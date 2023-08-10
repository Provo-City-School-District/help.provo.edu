<?php
function connect_help_db() {
    $DATABASE_HOST = getenv('HELPMYSQL_HOST');
    $DATABASE_USER = getenv('HELPMYSQL_USER');
    $DATABASE_PASS = getenv('HELPMYSQL_PASSWORD');
    $DATABASE_NAME = getenv('HELPMYSQL_DATABASE');
    $DATABASE_PORT = getenv('HELPMYSQL_PORT');

    try {
        $pdo = new PDO('mysql:host=' . $DATABASE_HOST . ';port=' . $DATABASE_PORT . ';dbname=' . $DATABASE_NAME . ';charset=utf8', $DATABASE_USER, $DATABASE_PASS);
    } catch (PDOException $e) {
        echo 'Failed to connect to database! ' . $e->getMessage();
        exit();
    }
}

$database = connect_help_db();
?>