<?php
require_once("helpdbconnect.php");
require_once("functions.php");
require_once("authentication_utils.php");
require_once("ticket_utils.php");
require_once("email_utils.php");
require_once("template.php");

// https://stackoverflow.com/a/40419584
function msg_is_auto_submitted($mailbox, $msg_id)
{
    $header = imap_fetchheader($mailbox, $msg_id);
    $header_identifiers = array('Auto-Submitted:([\s]*)auto-([replied|notified|generated])');

    for ($x = 0; $x < count($header_identifiers); $x++) {
        if (preg_match('/' . $header_identifiers[$x] . '/is', $header)) {
            return true;
        }
    }

    return false;
}

class EmailMessage {
    public string $id;
    public string $uid;
    public string $ancestor_id;

    public bool $is_external;

    public string $sender;

    public string $subject;
    public string $body;

    private string $sender_username;

    const INTERNAL_HOSTNAME = 'provo.edu';


    public function __construct($connection, $msg_num) {
        $msg_header = imap_headerinfo($connection, $msg_num);
        if ($msg_header === false) {
            throw new Exception("Failed to get header information for message $msg_num");
        }

        $this->id = $msg_header->message_id;
        $this->uid = imap_uid($connection, $msg_num);

        $this->ancestor_id = null;
        if (property_exists($msg_header, "in_reply_to")) {
            $this->ancestor_id = $msg_header->in_reply_to;
        }

        $sender_host = strtolower($msg_header->from[0]->host);
        $this->sender_username = $msg_header->from[0]->mailbox;

        $this->is_external = false;

        // Detect external emails
        if ($sender_host != EmailMessage::INTERNAL_HOSTNAME) {
            $this->is_external = true;
        }

        $this->sender = strtolower($this->sender_username . '@' . $sender_host);
        $this->subject = isset($msg_header->subject) ? $msg_header->subject : "";
    }

    public function get_username() {
        if ($this->is_external) {
            return "External sender: {$this->sender_username}";
        }

        return $this->sender_username;
    }
}

class EmailParser {
    private array $blacklisted_emails;
    private IMAP\Connection $connection;

    public function __construct($path, $username, $password, $blacklist) {
        $this->connection = imap_open($path, $username, $password);
        if ($this->connection === false) {
            throw new Exception("Mailbox $path failed to open");
        }

        $this->blacklisted_emails = $blacklist;
    }

    public function __destruct() {
        imap_close($this->connection);
    }

    private function parse_message(int $msg_num) {
        $msg = new EmailMessage($this->connection, $msg_num);

        // Ignore blacklisted emails
        if (in_array($msg_sender_email, $this->blacklisted_emails)) {
            log_app(LOG_INFO, "Received email from $msg_sender_email but it is on the blacklist. Ignoring...");
            imap_mail_move($this->connection, $msg->uid, "[Gmail]/Important", CP_UID);
            return;
        }

        // Ignore auto-reply emails
        if (msg_is_auto_submitted($mbox, $i)) {
            log_app(LOG_INFO, "Ignoring email from $sender_email as it is an auto-reply..");
            imap_mail_move($this->connection, $msg->uid, "[Gmail]/Important", CP_UID);
            return;
        }

        // Attempt to create local user if they aren't in the system
        $msg_username = $msg->get_username();
        if (!user_exists_locally($msg_username)) {
            if (create_user_in_local_db($msg_username) != CreateLocalUserStatus::Success) {
                log_app(LOG_ERR, "Failed to create local user $msg_username. Ignoring...");
            }
        }
    }

    public function parse_messages() {
        // Parse the oldest messages first
        imap_sort($mbox, SORTDATE, false);

        $msg_count = imap_num_msg($this->connection);
        for ($i = 1; $i <= $msg_count; $i++) {
            $this->parse_message($i);
        }
    }
}

$blacklisted_emails = [
    "dev@provo.edu",
    "help@provo.edu",
    "helpdesk@provo.edu"
];

$mbox_path = '{imap.gmail.com:993/imap/ssl}INBOX';
$parser = new EmailParser(
    $mbox_path, 
    getenv("GMAIL_USER"), 
    getenv("GMAIL_PASSWORD"), 
    $blacklisted_emails);