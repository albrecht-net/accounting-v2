<?php
// Deactivate error reporting
$driver = new mysqli_driver();
$driver->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

class DbSysLinkException extends RuntimeException {
    public function __construct(string $message = "", int $code = 0, throwable|null $previous = null) {
        trigger_error("SystemDatabaseException #" . $code . " - " . $message, E_USER_ERROR);
        parent::__construct($message, $code, $previous);
    }
}

class DbUsrLinkException extends RuntimeException {
    public function __construct(string $message = "", int $code = 0, throwable|null $previous = null) {
        trigger_error("JournalDatabaseException #" . $code . " - UID: "  . USER_ID . " - " . $message, E_USER_NOTICE);
        parent::__construct($message, $code, $previous);
    }}

class db {
    /**
     * @var object     $_instance_sys_link Object of the instantiated class for the system database.
     */
    private static $_instance_sys_link = null;

    /**
     * @var object     $_instance_usr_link Object of the instantiated class for the user database.
     */
    private static $_instance_usr_link = null;

    /**
     * @var int        $_mode          Mode selector, -1: System database, otherwise user_id (int != -1).
     */
    private $_mode;

    /**
     * @var object     $_mysqli        Object which represents the connection to the MySQL Server.
     */
    private $_mysqli;

    /**
     * @var object     $_stmt          Object which represents a prepared statement.
     */
    private $_stmt;

    /**
     * @var object     $_result        Object which represents the result set obtained from a query against the database. 
     */
    private $_result;

    /**
     * @var int|string $affected_rows  Number of affected rows in a previous MySQL operation.
     */
    public $affected_rows = 0;

    /**
     * @var int|string $ainsert_id     Value generated for an AUTO_INCREMENT column by the last query
     */
    public $insert_id = 0;

    /**
     * @var string     $server_info    Version of the MySQL server.
     */
    public $server_info;

    /**
     * Entrypoint for every mysql connection.
     * When the selected database is called up for the first time, a new instance will get created.
     * 
     * @param int      $mode           -1: System database with credentials from config. Otherwise user_id (int != -1) for user database with credentials from system database.
     * @return object                  Return the instantiated object of the choosen database.
     */
    public static function init(int $mode = -1) {
        if ($mode == -1) {
            if (!isset(self::$_instance_sys_link)) {
                self::$_instance_sys_link = new self(-1);
            }
            return self::$_instance_sys_link;
        } else {
            if (!isset(self::$_instance_sys_link)) {
                self::$_instance_sys_link = new self(-1);
            }
            if (!isset(self::$_instance_usr_link)) {
                self::$_instance_usr_link = new self($mode);
            }
            if (isset(self::$_instance_usr_link) && self::$_instance_usr_link->_mode != $mode) {
                self::$_instance_usr_link = new self($mode);
            }
            return self::$_instance_usr_link;
        }
    }

    /**
     * Constructor
     * 
     * @param int      $mode           0: System database with credentials from config. Otherwise user_id (int != 0) for user database with credentials from system database.
     * @return void                    No value is returned
     */
    private function __construct(int $mode) {
        $this->_mode = $mode;
        if ($mode == -1) {
            $this->_connect_sys_db();
        } else {
            $this->_connect_usr_db();
        }
    }

    /**
     * Open a new connection to the MySQL server for system database.
     * For the database credentials the entries from the config will be used.
     * 
     * @throws                         DbSysLinkException if some connection error occoured
     * @return void                    No value is returned
     */
    private function _connect_sys_db() {
        try {
            $this->_mysqli = new mysqli(config::get('db.host'), config::get('db.username'), config::get('db.password'), config::get('db.name'), config::get('db.port'));

            $this->_stmt = $this->_mysqli->stmt_init();
    
            $this->server_info = $this->_mysqli->server_info;
        } catch (mysqli_sql_exception $e) {
            throw new DbSysLinkException("Cannot connect to system database, check config.php. MySQL said: " . $e->getMessage(), $e->getCode(), $e);
        }

        return;
    }

    /**
     * Open a new connection to the MySQL server for user database.
     * For the database credentials the userID (stored in $_mode) will be used.
     * 
     * @throws                         DbUsrLinkException if no user database credentials where found for the selected user or some connection error occoured while connection to the user database
     * @return void                    No value is returned
     */
    private function _connect_usr_db() {
        try {
            self::$_instance_sys_link->run_query("SELECT `db_host`, `db_port`, `db_username`, `db_password`, `db_name` FROM `databases` WHERE `user_id`=? LIMIT 1", "i", $this->_mode);

            if (self::$_instance_sys_link->num_rows() != 1) {
                throw new DbUsrLinkException("No user database credentials found for current user.");
                return;
            }

            $result = self::$_instance_sys_link->fetch_array()[0];
            $this->_mysqli = new mysqli($result['db_host'], $result['db_username'], $result['db_password'], $result['db_name'], $result['db_port']);

            $this->_stmt = $this->_mysqli->stmt_init();
            $this->server_info = $this->_mysqli->server_info;
        } catch (mysqli_sql_exception $e) {
            throw new DbUsrLinkException($e->getMessage(), $e->getCode(), $e);
        }

        return;
    }

    /**
     * Wrapper for mysqli_stmt::prepare, mysqli_stmt::bind_param and mysqli_stmt::execute, creates a mysqli_result
     * 
     * @param string   $query          The query, as a string. It must consist of a single SQL statement.
     * @param string   $types          Optional. A string that contains one or more characters which specify the types for the corresponding bind variables.
     * @param mixed    $var            Optional (Required if $types is set). Parameter. The number of variables and length of string $types must match the parameters in the statement.
     * @param mixed    $vars           Optional (Required if $types is set). Additional parameters. The number of variables and length of string $types must match the parameters in the statement.
     * @throws                         DbSysLinkException if mode selector is equal to -1
     * @throws                         DbUsrLinkException if mode selector is not equal to -1
     * @return bool                    Returns true on success or false on failure.
     */
    public function run_query(string $query, string|null $types = null, mixed $var = null, mixed ...$vars) {
        try {
            $this->_stmt->prepare($query);
            if (!empty($types)) {
                $this->_stmt->bind_param($types, $var, ...$vars);
            }
            $this->_stmt->execute();
            $this->_result = $this->_stmt->get_result();
            $this->affected_rows = $this->_mysqli->affected_rows;
            $this->insert_id = $this->_mysqli->insert_id;
        } catch (mysqli_sql_exception $e) {
            if ($this->_mode == -1) {
                throw new DbSysLinkException($e->getMessage(), $e->getCode(), $e);
            } else {
                throw new DbUsrLinkException($e->getMessage(), $e->getCode(), $e);
            }

            return false;
        }

        return true;
    }

    /**
     * Gets the number of rows in a result.
     * 
     * @return integer                 Number of rows in the result set.
     */
    public function num_rows() {
        return $this->_result->num_rows;
    }

    /**
     * Gets the number of rows in a table without any filter applied.
     * 
     * @param string   $table          Table name.
     * @return integer                 Number of rows in the table.
     */
    public function num_rows_all(string $table) {
        $query = "SELECT COUNT(*) as `count` FROM `" . $table . "`";
        if ($this->_mode == -1) {
            self::$_instance_sys_link->run_query($query);
            $result = self::$_instance_sys_link->fetch_array()[0]['count'];
        } else {
            self::$_instance_usr_link->run_query($query);
            $result = self::$_instance_usr_link->fetch_array()[0]['count'];
        }

        return $result;
    }

    /**
     * Fetches all result rows as an associative array.
     * 
     * @return array                   Result of all rows as array.
     */
    public function fetch_array() {
        return $this->_result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Fetches one result row as an associative array.
     * 
     * @param int      $offset         Adjusts the result pointer to the given offset and return one result row as array
     * @return array                   Returns an array representing the fetched row or empty array if there are no more rows in the result or on failure.
     */
    public function fetch_one(int $offset = 0) {
        if ($this->num_rows() < 1) {
            return array();
        }

        if (!$this->_result->data_seek($offset)) {
            return array();
        }

        $_result = $this->_result->fetch_array(MYSQLI_ASSOC);
        if ($_result === false | $_result === null) {
            return array();
        } else {
            return $_result;
        }
    }
}