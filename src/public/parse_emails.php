<?php
require_once from_root("/../vendor/autoload.php");
require_once("helpdbconnect.php");
require_once("functions.php");
require_once("authentication_utils.php");
require_once("ticket_utils.php");
require_once("email_utils.php");
require_once("template.php");

use Webklex\PHPIMAP\ClientManager;


$blacklisted_emails = [
    "dev@provo.edu",
    "help@provo.edu",
    "helpdesk@provo.edu"
];

$move_emails_after_parsed = true;

$imap_url = '{imap.gmail.com:993/imap/ssl}INBOX';
$username = getenv("GMAIL_USER");
$password = getenv("GMAIL_PASSWORD");

$cm = new ClientManager(from_root('../php-includes/imap_conf.php'));


$client = $cm->account('dev');
$client->connect();

$folder = $client->getFolders()[0];
$messages = $folder->messages()->all()->get();

foreach ($messages as $message) {
    echo $message->getSubject().'<br />';
    echo 'Attachments: '.$message->getAttachments()->count().'<br />';
    echo $message->getHTMLBody();
    
    //Move the current Message to 'INBOX.read'
    if ($message->move('INBOX.read') == true) {
        echo 'Message has been moved';
    } else {
        echo 'Message could not be moved';
    }
}
