<?php
class SolarWindsDB {
    private static $shared_db = null;
    private $connection;

    private function __construct() {
        $this->connection = mysqli_connect(getenv("SWHELPDESKHOST"), getenv("SWHELPDESKUSER"), getenv("SWHELPDESKPASSWORD"), getenv("SWHELPDESKDATABASE"));
        if (!$this->connection) {
            die('Could not connect to MySQL: ' . mysqli_connect_error());
        }   
    }

    function __destruct() {
        mysqli_close($this->connection);
    }

    public static function get() {
        if (self::$shared_db == null) {
            self::$shared_db = new SolarWindsDB();
        }
        return self::$shared_db->connection;
    }
}
?>