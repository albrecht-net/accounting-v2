<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__, 3) . DIRECTORY_SEPARATOR);
}

// Allowed request types
define('ACCESS_CONTROL_ALLOW_METHODS', array('GET', 'PUT', 'DELETE'));

// Require user authentication
define('REQUIRE_AUTH', true);

require_once ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'init.php';

// Get current user database configuration and verify connection
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        // Get journal database credentials (except password) 
        db::init()->run_query("SELECT `db_host`, `db_port`, `db_username`, `db_name` FROM `databases` WHERE user_id=?", "i", USER_ID);

        if (db::init()->count() != 1 ) {
            response::result(array('db_host'=>null, 'db_port'=>null, 'db_username'=>null, 'db_name'=>null));
        } else{
            response::result(db::init()->fetch_one());
        }

        // Get server_info from journal db
        response::result(array('server_info'=>db::init(USER_ID)->server_info));
    } catch (DbSysLinkException $e) {
        response::error('Internal application error occurred.');
        response::send(false, 500);
        exit;
    } catch (DbUsrLinkException $e) {
        response::error("Cannot establish test connenction to user database. MySQL said: #" . $e->getCode() . " - " . $e->getMessage(), $e->getCode());
        response::result(array('server_info'=>'unknown'));
    }

    response::send(true, 200);
    exit;

// Replace current user database configuration and verify connection
} elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    try {
        $request_body = array(
            'db_host' => request::body('db_host', true),
            'db_port' => request::body('db_port', true, false, FILTER_VALIDATE_INT),
            'db_username' => request::body('db_username', true),
            'db_password' => request::body('db_password', true, false),
            'db_name' => request::body('db_name', true),
            'force' => request::body('force', false, false, FILTER_VALIDATE_BOOL, array('options' => array('default' => false)))
        );

        // Deactivate error reporting
        $driver = new mysqli_driver();
        $driver->report_mode = MYSQLI_REPORT_STRICT;
        
        // Open temporary connection to user database and get server_info
        $_tmp_mysqli = new mysqli($request_body['db_host'], $request_body['db_username'], $request_body['db_password'], $request_body['db_name'], $request_body['db_port']);
        response::result(array('server_info'=>$_tmp_mysqli->server_info));
        
        // Insert or replace user database configuration
        db::init()->run_query("INSERT INTO `databases` (`user_id`, `db_host`, `db_port`, `db_username`, `db_password`, `db_name`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `db_host`=?, `db_port`=?, `db_username`=?, `db_password`=?, `db_name`=?", "isissssisss", USER_ID, $request_body['db_host'], $request_body['db_port'], $request_body['db_username'], $request_body['db_password'], $request_body['db_name'], $request_body['db_host'], $request_body['db_port'], $request_body['db_username'], $request_body['db_password'], $request_body['db_name']);
    } catch (JsonException $e) {
        response::error('Faulty request data. JSON ' . $e->getMessage());
        response::send(false, 400);
        exit;
    } catch (RequestException $e) {
        response::error($e->getMessage());
        response::send(false, 400);
        exit;
    } catch (mysqli_sql_exception $e) {
        response::error("Cannot connect to user database, check given credentials. MySQL said: #" . $e->getCode() . " - " . $e->getMessage(), $e->getCode());
        response::result(array('server_info'=>'unknown'));
    
        if ($request_body['force'] == false) {
            response::send(false, 400);
            exit;
        }
    } catch (DbSysLinkException $e) {
        response::error('Internal application error occurred.');
        response::send(false, 500);
        exit;
    }

    response::send(true, 201);
    exit;

// Delete current user database configuration
} elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    try {
        db::init()->run_query("DELETE FROM `databases` WHERE user_id=?", "i", USER_ID);
    } catch (DbSysLinkException $e) {
        response::error('Internal application error occurred.');
        response::send(false, 500);
        exit;
    }

    response::send(true, 200);
    exit;
}
