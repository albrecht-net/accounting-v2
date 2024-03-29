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
        db::init()->run_query("SELECT `db_host`, `db_port`, `db_username`, `db_name` FROM `databases` WHERE user_id=?", "i", USER_ID);
    } catch (exception_sys_link $e) {
        trigger_error("#" . $e->getCode() . " - " . $e->getMessage(), E_USER_ERROR);

        response::error('Internal application error occurred.');
        response::send(false, 500);
        exit;
    }

    if (db::init()->count() != 1 ) {
        response::result(array('db_host'=>null, 'db_port'=>null, 'db_username'=>null, 'db_name'=>null));
    } else{
        response::result(db::init()->fetch_one());
    }

    try {
        response::result(array('server_info'=>db::init(USER_ID)->server_info));
    } catch (exception_sys_link $e) {
        trigger_error("#" . $e->getCode() . " - " . $e->getMessage(), E_USER_ERROR);

        response::error('Internal application error occurred.');
        response::send(false, 500);
        exit;
    } catch (exception_usr_link $e) {
        trigger_error('uid: ' . USER_ID . " #" . $e->getCode() . " - " . $e->getMessage(), E_USER_NOTICE);

        response::error("Cannot establish test connenction to user database. MySQL said: #" . $e->getCode() . " - " . $e->getMessage(), $e->getCode());
        response::result(array('server_info'=>'unknown'));
    }

    response::send(true, 200);
    exit;

// Replace current user database configuration and verify connection
} elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    try {
        $request_params = array(
            'force' => request::body('force', false, false, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
        );
        $request_data = array(
            'db_host' => request::body('db_host', true),
            'db_port' => request::body('db_port', true, false, FILTER_VALIDATE_INT),
            'db_username' => request::body('db_username', true),
            'db_password' => request::body('db_password', true, false),
            'db_name' => request::body('db_name', true)
        );
    } catch (JsonException $e) {
        response::error('Invalid or missing request data.');
        response::send(false, 400);
        exit;
    } catch (exception_request $e) {
        response::error($e->getMessage());
        response::send(false, 400);
        exit;
    }

    // Open temporary connection to user database
    try {
        $_tmp_mysqli = new mysqli($request_data['db_host'], $request_data['db_username'], $request_data['db_password'], $request_data['db_name'], $request_data['db_port']);
    } catch (mysqli_sql_exception $e) {
        response::error("Cannot connect to user database, check given credentials. MySQL said: #" . $e->getCode() . " - " . $e->getMessage(), $e->getCode());
    
        if ($request_params['force'] == false) {
            response::send(false, 400);
            exit;
        }
    }

    // Insert or replace user database configuration
    try {
        db::init()->run_query("INSERT INTO `databases` (`user_id`, `db_host`, `db_port`, `db_username`, `db_password`, `db_name`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `db_host`=?, `db_port`=?, `db_username`=?, `db_password`=?, `db_name`=?", "isissssisss", USER_ID, $request_data['db_host'], $request_data['db_port'], $request_data['db_username'], $request_data['db_password'], $request_data['db_name'], $request_data['db_host'], $request_data['db_port'], $request_data['db_username'], $request_data['db_password'], $request_data['db_name']);
    } catch (exception_sys_link $e) {
        trigger_error("#" . $e->getCode() . " - " . $e->getMessage(), E_USER_ERROR);

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
    } catch (exception_sys_link $e) {
        trigger_error("#" . $e->getCode() . " - " . $e->getMessage(), E_USER_ERROR);

        response::error('Internal application error occurred.');
        response::send(false, 500);
        exit;
    }

    response::send(true, 200);
    exit;
}
