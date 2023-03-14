<?php
class db {
    /**
     * @var object $_instance_sys_link Object of the instantiated class for the systemdatabase.
     */
    private static $_instance_sys_link = null;

    /**
     * @var object $_instance_usr_link Object of the instantiated class for the userdatabase.
     */
    private static $_instance_usr_link = null;

    /**
     * @var object $_mysqli MySQL server connection.
     */
    private $_mysqli;

    /**
     * @var string $_query SQL query from current query.
     */
    private $_query;

    /**
     * @var object $_result SQL result object.
     */
    private $_result;

    /**
     * Constructor
     * 
     * @param integer $type 1: Systemdatabase with credentials from config. 2: Userdatabase with credentials from systemdatabase.
     * @return bool Return true if connection to selected database was successfull otherwise false.
     */
    private function __construct($type) {
        switch ($type) {
            case 1:
                if (!$this->_connect_sys_db()) {
                    return false;
                } else {
                    return true;
                }

            case 2:
                if (!$this->_connect_usr_db()) {
                    return false;
                } else {
                    return true;
                }
            
            default:
                return false;
        }
    }

    /**
     * Open a new connection to the MySQL server for systemdatabase.
     * For the database credentials the entries from the config will be used.
     * 
     * @return bool Return true if connection to database was successfull otherwise false.
     */
    private function _connect_sys_db() {
        $this->_mysqli = new mysqli(config::get('dbHost') . ':' . config::get('dbPort'), config::get('dbUsername'), config::get('dbPassword'), config::get('dbName'));

        if ($this->validate_connection() !== 2) {
            trigger_error('Cannot connect to system database. Check config.php. (' . mysqli_connect_errno() . ') ' . mysqli_connect_error(), E_USER_ERROR);
            return false;
        }

        // Connection established
        return true;
    }

    /**
     * Open a new connection to the MySQL server for userdatabase.
     * For the database credentials the userID and dbID stored in the session will be used. userDbSet must be true in the session otherwise it return false.
     * 
     * @return bool Return true if connection to database was successfull otherwise false.
     */
    private function _connect_usr_db() {
        if (session::get('userDbSet')) {
            $sqlquery = "SELECT dbHost, dbPort, dbUsername, dbPassword, dbName FROM `databases` WHERE dbID = " . intval(session::get('userDbID')) . " AND userID = " . intval(session::get('userID'));
            
            if (self::$_instance_sys_link->query($sqlquery)) {
                if (self::$_instance_sys_link->count() == 1) {
                    $result = self::$_instance_sys_link->first();
                    $this->_mysqli = new mysqli($result['dbHost'], $result['dbUsername'], $result['dbPassword'], $result['dbName'], $result['dbPort']);

                    if ($this->validate_connection() !== 2) {
                        trigger_error('Cannot connect to user database with ID (' . intval(session::get('userDbID')) . ')', E_USER_WARNING);
                        return false;
                    }

                    // Connection established
                    return true;
                }
            } 
        }

        // Return false if userdatabase not set, query for database selection failed, no database found or settings where invalid
        return false;
    }

    /**
     * Entrypoint for every mysql connection.
     * When the selected database is called up for the first time, a new instance will get created.
     * 
     * @param integer $type 1: Systemdatabase with credentials from config. 2: Userdatabase with credentials from systemdatabase.
     * @return object|null Return the instantiated object of the choosen database.
     */
    public static function init($type) {
        if ($type === 1) {
            if (!isset(self::$_instance_sys_link)) {
                self::$_instance_sys_link = new self(1);
            }
            return self::$_instance_sys_link;
        } elseif ($type === 2) {
            if (!isset(self::$_instance_sys_link)) {
                self::$_instance_sys_link = new self(1);
            }
            if (!isset(self::$_instance_usr_link)) {
                self::$_instance_usr_link = new self(2);
            }
            return self::$_instance_usr_link;
        }

        return;
    }

    /**
     * Validate if a connection to the mysql server is established
     * 
     * @return int Return 0: if no mysqli instance found, 1: Connection failed, 2: Connection established
     */
    public function validate_connection() {
        if ($this->_mysqli !== null) {
            if ($this->_mysqli->connect_error !== null) {
                // Connection failed
                return 1;
            } else {
                // Connection established
                return 2;
            };
        }

        // No mysqli instance found. Only possible while using usrDB.
        return 0;
    }

    /**
     * Escapes special characters in a string for use in an SQL statement.
     * 
     * @param string $escapestr The string to be escaped.
     * @return string Returns an escaped string.
     */
    public function escape_string($escapestr) {
            return $this->_mysqli->real_escape_string($escapestr);
    }

    /**
     * Execute an SQL query and store (if a result set is given) the result for further use.
     * 
     * @param string $sqlquery The query as a string.
     * @return bool Returns true on success or false on failure.
     */
    public function query($sqlquery) {
        $this->_query = $sqlquery;

        if ($this->_mysqli->real_query($this->_query)) {
            if ($this->_mysqli->field_count > 0) {
                $this->_result = $this->_mysqli->store_result();
            }
            return true;
        } else {
            // MySql Error
            return false;
        }

        // Error, systemdatabase not connected
        return $this;
    }

    /**
     * Return a result object with the pointer set to the field given by the offset.
     * 
     * @param integer $offset Adjusts the result pointer to an row in the result.
     * @return object|bool Return result object on success or false if given offset was invalid.
     */
    public function result($offset = 0) {
        if ($this->_result->data_seek($offset)) {
            return $this->_result;
        }

        // Offset invalid
        return false;
    }

    /**
     * Fetches all result rows as an associative array, a numeric array, or both.
     * 
     * @return array Result as array.
     */
    public function fetch_array() {
        return $this->result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Fetches the first result row as an associative array.
     * 
     * @return array Result as array.
     */
    public function first() {
        return $this->fetch_array()[0];
    }

    /**
     * Gets the number of rows in a result.
     * 
     * @return integer Number of rows in the result set.
     */
    public function count() {
        return $this->result()->num_rows;
    }

    /**
     * Returns a list of errors from the last command executed.
     * 
     * @return array A list of errors, each as an associative array containing the errno, error, and sqlstate.
     */
    public function error() {
        return $this->_mysqli->error_list;
    }

    /**
     * Frees the memory associated with a result.
     * 
     * @return No value is returned.
     */
    public function free() {
        return $this->_result->free_result();
    }

    public function close() {
        return $this->_mysqli->close();
    }
}