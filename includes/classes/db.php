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
     * @var object $_mysqli Object which represents the connection to the MySQL Server.
     */
    private $_mysqli;

    public int $errno;

    public $_stmt;

    public $_result;

    /**
     * Constructor
     * 
     * @param integer $type 1: Systemdatabase with credentials from config. 2: Userdatabase with credentials from systemdatabase.
     * @return void No value is returned
     */
    private function __construct($type) {
        switch ($type) {
            case 1:
                $this->_connect_sys_db();
                break;
            case 2:
                $this->_connect_usr_db();
                break;
        }

        return;
    }

    /**
     * Open a new connection to the MySQL server for systemdatabase.
     * For the database credentials the entries from the config will be used.
     * 
     * @return void No value is returned
     */
    private function _connect_sys_db() {
        $this->_mysqli = new mysqli(config::get('db.host') . ':' . config::get('db.port'), config::get('db.username'), config::get('db.password'), config::get('db.name'));
        $this->errno = $this->_mysqli->connect_errno;

        if ($this->_mysqli->connect_errno) {
            trigger_error('Cannot connect to system database, check config.php. MySQL said: #' . mysqli_connect_errno() . ' - ' . mysqli_connect_error(), E_USER_ERROR);
            return;
        }

        $this->_stmt = $this->_mysqli->stmt_init();

        return;
    }

    /**
     * Entrypoint for every mysql connection.
     * When the selected database is called up for the first time, a new instance will get created.
     * 
     * @param int $type 1: Systemdatabase with credentials from config. 2: Userdatabase with credentials from systemdatabase.
     * @return object|void Return the instantiated object of the choosen database.
     */
    public static function init(int $type) {
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

    public function run_query() {
        $this->_stmt->execute();
        $this->errno = $this->_stmt->errno;
        $this->_result = $this->_stmt->get_result();
    }

}