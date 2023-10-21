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
    $_tmp_mysqli = new mysqli($request_data['db.host'], $request_data['db.username'], $request_data['db.password'], $request_data['db.name'], $request_data['db.port']);

    if ($_tmp_mysqli->connect_errno) {
        // Close temporary connection
        $_tmp_mysqli->close();

        response::error("Cannot connect to system database, check config.php. MySQL said: #" . mysqli_connect_errno() . " - " . mysqli_connect_error(), mysqli_connect_errno());

        if ($request_data['force'] == false) {
            response::send(false, 400);
            exit;
        }
    } else {
        // Close temporary connection
        $_tmp_mysqli->close();
    }


// Delete current user database configuration
} elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {

}
