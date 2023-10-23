<?php

require('../vendor/autoload.php');

use Monolog\Logger;
use Monolog\Handler\GelfHandler;
use Gelf\Publisher;
use Gelf\Transport\UdpTransport;

function logMessage($message) {

    
    // Create a Logger
    $log = new Logger('Help Desk Logs');

    // Create a GELF handler and set up the transport to your Graylog server
    $transport = new UdpTransport(getenv('GRAYLOGIP'), getenv('GRAYLOGPORT'));
    $publisher = new Publisher($transport);
    $gelfHandler = new GelfHandler($publisher);

    // Add the GELF handler to the logger
    $log->pushHandler($gelfHandler);

    // Log the message
    $log->error($message);
}

logMessage('User ' . 'test' . ' logged in at ' . date("Y-m-d H:i:s"));