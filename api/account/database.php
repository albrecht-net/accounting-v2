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

// Replace current user database configuration and verify connection
} elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    try {
        $request_data = array(
            'db_host' => trim(request::body('db_host')),
            'db_port' => trim(request::body('db_port')),
            'db_username' => trim(request::body('db_username')),
            'db_password' => request::body('db_password'),
            'db_name' => trim(request::body('db_name')),
            'force' => empty(request::body('force')) ? false : boolval(request::body('force'))
        );
    } catch (JsonException $e) {
        response::error('Missing request data.');
        response::send(false, 400);
        exit;
    }

    // Open temporary connection to user database
    try {
        $_tmp_mysqli = new mysqli($request_data['db_host'], $request_data['db_username'], $request_data['db_password'], $request_data['db_name'], $request_data['db_port']);
    } catch (mysqli_sql_exception $e) {
        response::error("Cannot connect to user database, check given credentials. MySQL said: #" . $e->getCode() . " - " . $e->getMessage(), $e->getCode());
    
        if ($request_data['force'] == false) {
            response::send(false, 400);
            exit;
        }
    }

    // Insert new row to sessions table
    try {
        db::init()->run_query("INSERT INTO `databases` (`user_id`, `db_host`, `db_port`, `db_username`, `db_password`, `db_name`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `db_host` = ?, `db_port` = ?, `db_username` = ?, `db_password` = ?, `db_name` = ?", "isissssisss", USER_ID, $request_data['db_host'], $request_data['db_port'], $request_data['db_username'], $request_data['db_password'], $request_data['db_name'], $request_data['db_host'], $request_data['db_port'], $request_data['db_username'], $request_data['db_password'], $request_data['db_name']);
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

}
