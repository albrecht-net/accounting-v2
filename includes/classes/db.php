<?php
class db {
    /**
     * @var object $_instance_sys_link Object of the instantiated class for the system database.
     */
    private static $_instance_sys_link = null;

    /**
     * @var object $_instance_usr_link Object of the instantiated class for the user database.
     */
    private static $_instance_usr_link = null;

    /**
     * @var int $_user_id Integer which stores the given user_id for the user database.
     */
    private $_user_id;

    /**
     * @var object $_mysqli Object which represents the connection to the MySQL Server.
     */
    private $_mysqli;
    
    private $_stmt;
    public $_result;

    /**
     * @var int $errno Error code for the most recent function call
     */
    public $errno;

    /**
     * @var string $error Description for last statement error
     */
    public $error;


    /**
     * Constructor
     * 
     * @param int $type 1: System database with credentials from config. 2: User database with credentials from system database.
     * @param int $user_id: Set userID to query credentials for user database (optional, only required for connection to user database).
     * @return void No value is returned
     */
    private function __construct(int $type, int $user_id = null) {
        switch ($type) {
            case 1:
                $this->_connect_sys_db();
                break;
            case 2:
                if ($user_id === null) {
                    break;
                }
                $this->_user_id = $user_id;

                $this->_connect_usr_db($user_id);
                break;
        }

        return;
    }

    /**
     * Open a new connection to the MySQL server for system database.
     * For the database credentials the entries from the config will be used.
     * 
     * @return void No value is returned
     */
    private function _connect_sys_db() {
        $this->_mysqli = new mysqli(config::get('db.host'), config::get('db.username'), config::get('db.password'), config::get('db.name'), config::get('db.port'));
        $this->errno = $this->_mysqli->connect_errno;

        if ($this->_mysqli->connect_errno) {
            trigger_error('Cannot connect to system database, check config.php. MySQL said: #' . mysqli_connect_errno() . ' - ' . mysqli_connect_error(), E_USER_ERROR);
            return;
        }

        $this->_stmt = $this->_mysqli->stmt_init();

        return;
    }

    /**
     * Open a new connection to the MySQL server for user database.
     * For the database credentials the userID will be used. userDbSet must be true in the session otherwise it return false.
     * 
     * @param int $user_id: Set userID to query credentials for user database.
     * @return void No value is returned
     */
    private function _connect_usr_db(int $user_id) {
        if (!self::$_instance_sys_link->prepare("SELECT db_host, db_port, db_username, db_password, db_name FROM databases WHERE user_id=? LIMIT 1")) {
            return;
        }

        if (!self::$_instance_sys_link->bind_param("i", $user_id)) {
            return;
        }

        if (!self::$_instance_sys_link->run_query()) {
            return;
        }

        $result = self::$_instanceSysLink->fetch_array();
        $this->_mysqli = new mysqli($result['db_host'], $result['db_username'], $result['db_password'], $result['db_name'], $result['db_port']);
        $this->errno = $this->_mysqli->connect_errno;

        if ($this->_mysqli->connect_errno) {
            trigger_error('User #' . $user_id . ' cannot connect to user database. MySQL said: #' . mysqli_connect_errno() . ' - ' . mysqli_connect_error(), E_USER_NOTICE);
            return;
        }

        $this->_stmt = $this->_mysqli->stmt_init();

        return;
    }

    /**
     * Entrypoint for every mysql connection.
     * When the selected database is called up for the first time, a new instance will get created.
     * 
     * @param int $type 1: System database with credentials from config. 2: User database with credentials from system database.
     * @param int $user_id: Set userID to query credentials for user database (optional, only required for connection to user database).
     * @return object|void Return the instantiated object of the choosen database or if invalid $type was given no value.
     */
    public static function init(int $type, int $user_id = null) {
        if ($type === 1) {
            if (!isset(self::$_instance_sys_link)) {
                self::$_instance_sys_link = new self(1);
            }
            return self::$_instance_sys_link;
        } elseif ($type === 2) {
            if (!isset(self::$_instance_sys_link)) {
                self::$_instance_sys_link = new self(1);
            }
            if (!isset(self::$_instance_usr_link) | self::$_instance_usr_link !== $user_id) {
                self::$_instance_usr_link = new self(2, $user_id);
            }
            return self::$_instance_usr_link;
        }

        return;
    }

    /**
     * Wrapper for mysqli_stmt::prepare
     * 
     * @param string $query The query, as a string. It must consist of a single SQL statement.
     * @return bool Returns true on success or false on failure.
     */
    public function prepare(string $query) {
        return $this->_stmt->prepare($query);
    }

    /**
     * Wrapper for mysqli_stmt::bind_param
     * 
     * @param string $types A string that contains one or more characters which specify the types for the corresponding bind variables.
     * @param mixed $var Parameter
     * @param mixed $vars Additional parameters. The number of variables and length of string types must match the parameters in the statement. 
     * @return bool Returns true on success or false on failure.
     */
    public function bind_param(string $types, &$var, &...$vars) {
        return $this->_stmt->bind_param($types, $var, ...$vars);
    }

    /**
     * Runs mysqli_stmt::execute and create a mysqli_result
     * 
     * @return bool Returns true on success or false on failure.
     */
    public function run_query() {
        if (!$this->_stmt->execute()) {
            return false;
        }
        if (!$this->_result = $this->_stmt->get_result()) {
            return false;
        }
        return true;
    }

    /**
     * Gets the number of rows in a result.
     * 
     * @return integer Number of rows in the result set.
     */
    public function count() {
        return $this->_result->num_rows;
    }

    /**
     * Fetches all result rows as an associative array.
     * 
     * @return array Result of all rows as array.
     */
    public function fetch_array() {
        return $this->_result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Fetches one result row as an associative array.
     * 
     * @param int $offset Adjusts the result pointer to the given offset and return one result row as array
     * @return array|void|bool Returns an array representing the fetched row, null if there are no more rows in the result set, or false on failure.
     */
    public function fetch_one(int $offset = 0) {
        if (!$this->_result->data_seek($offset)) {
            return false;
        }
        return $this->_result->fetch_array(MYSQLI_ASSOC);
    }
}