<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__, 3) . DIRECTORY_SEPARATOR);
}

// Set flag for required user authentication to false if not defined
if (!defined('REQUIRE_AUTH')) {
    define('REQUIRE_AUTH', false);
}

/**
 * Authenticate user
 * Validate session id (given as [1] parameter, [2] http autorization header (Bearer) or [3] cookie) and populate the constante 'USER_ID' on success
 * 
 * @param string $sid Input session id by string. Session id given by this parameter has higher prioity than http header or cookie.
 * @return bool Return true on successfull authentication otherwise false.
 */
function userAuthenticate(string $sid = null) {
    // Set session id
    if (($sid == null) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        if (substr($_SERVER['HTTP_AUTHORIZATION'], 0, 7) !== "Bearer ") {
            trigger_error("Wrong type of authentication scheme from [" . $_SERVER['REMOTE_ADDR'] . "]", E_USER_WARNING);
            define('USER_ID', 0);

            response::error('Wrong type of authentication scheme.');
            response::send(false, 401, 'WWW-Authenticate: Bearer');
            return false;
        }
        $sid = substr($_SERVER['HTTP_AUTHORIZATION'], 7);
    } elseif (($sid == null) && !empty($_COOKIE['sid'])) {
        $sid = $_COOKIE['sid'];
    } elseif ($sid == null) {
        trigger_error("No session id from [" . $_SERVER['REMOTE_ADDR'] . "]", E_USER_NOTICE);
        define('USER_ID', 0);

        response::error('Missing session id, provide session id over authorization header or cookie.');
        response::send(false, 401, 'WWW-Authenticate: Bearer');
        return false;
    }

    $time_now = time();

    // Get row matching the given session id
    try {
        db::init()->run_query("SELECT `user_id`, UNIX_TIMESTAMP(expiry_date) AS `expiry_date` FROM `sessions` WHERE id = ? LIMIT 1", "s", $sid);
    } catch (exception_sys_link $e) {
        trigger_error("#" . $e->getCode() . " - " . $e->getMessage(), E_USER_ERROR);
        define('USER_ID', 0);

        response::error('Internal application error occurred.');
        response::send(false, 500);
        return false;
    }

    // Check if session found
    if (db::init()->count() != 1) {
        define('USER_ID', 0);

        response::error('Invalid session id.');
        response::send(false, 401);
        return false;
    }

    // Check if session not expired
    if (db::init()->fetch_one()['expiry_date'] < $time_now) {
        define('USER_ID', 0);

        response::error('Invalid session id.');
        response::send(false, 401);
        return false;
    }

    define('USER_ID', db::init()->fetch_one()['user_id']);
    return true;
}

if (REQUIRE_AUTH !== true) {
    define('USER_ID', 0);
} elseif (!userAuthenticate()) {
    exit;
}
