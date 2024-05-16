<?php
class HelpDB {
    private static $shared_db = null;
    private $connection;

    private function __construct() {
        $this->connection = mysqli_connect(getenv("HELPMYSQL_HOST"), getenv("HELPMYSQL_USER"), getenv("HELPMYSQL_PASSWORD"), getenv("HELPMYSQL_DATABASE"));
        if (!$this->connection) {
            die('Could not connect to MySQL: ' . mysqli_connect_error());
        }   
    }

    function __destruct() {
        mysqli_close($this->connection);
    }

    public static function get() {
        if (self::$shared_db == null) {
            self::$shared_db = new HelpDB();
        }
        return self::$shared_db->connection;
    }
}
?>