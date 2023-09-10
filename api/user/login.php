<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__, 3) . DIRECTORY_SEPARATOR);
}

// Allowed request types
define('ACCESS_CONTROL_ALLOW_METHODS', array('POST'));

require_once ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'init.php';

header('Content-Type: application/json');

$request_data = array(
    'username' => trim($_POST['in-text-username']),
    'password' => $_POST['in-password-password'],
    'remember' => empty($_POST['chk-remember']) ? false : boolval($_POST['chk-remember'])
);

if (strlen($request_data['username']) < 1) {
    http_response_code(400);
    exit;
}

// Query users table by given username
try {
    db::init()->run_query("SELECT `id`, `password` FROM `users` WHERE username=? AND `status`='Y'", "s", $request_data['username']);
} catch (exception_sys_link $e) {
    trigger_error("#" . $e->getCode() . " - " . $e->getMessage(), E_USER_ERROR);
    http_response_code(500);
    exit;
}

if (db::init()->count() != 1) {
    trigger_error("Username '" . $request_data['username'] . "' was not found.", E_USER_NOTICE);
    http_response_code(401);
    exit;
}

if (!password_verify($request_data['password'], db::init()->fetch_one()['password'])) {
    trigger_error("Invalid credentials provided for user'" . $request_data['username'] . "'.", E_USER_NOTICE);
    http_response_code(401);
    exit;
}

// Cookie parameters
$arr_cookie_options = array(
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'strict'
);

$time_now = time();

// Set expire by date
if ($request_data['remember']) {
    $time_expire = $time_now + config::get('session.max_lifetime');

    // Set cookie expire timestamp
    $arr_cookie_options['expires'] = $time_expire;
} else {
    $time_expire = $time_now + config::get('session.inactive_time');

    // Set cookie expire timestamp
    $arr_cookie_options['expires'] = 0;
}

// Generate session id
$sid = bin2hex(random_bytes(32));

// Get user agent
$user_agent = explode(" ", $_SERVER['HTTP_USER_AGENT'], 2)[0];

// Insert new row to sessions table
try {
    db::init()->run_query("INSERT INTO `sessions` (`id`, `user_id`, `user_agent`, `ip_address`, `expiry_date`, `last_activity`) VALUES (?, ?, ?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?))", "sissss", $sid, db::init()->fetch_one()['id'], $user_agent, $_SERVER['REMOTE_ADDR'], $time_expire, $time_now);
} catch (exception_sys_link $e) {
    trigger_error("#" . $e->getCode() . " - " . $e->getMessage(), E_USER_ERROR);
    http_response_code(500);
    exit;
}


// Send cookie with additional parameters
setcookie('sid', $sid, $arr_cookie_options);

$response = array(
    'success' => true
);

echo json_encode($response);
http_response_code(200);
exit;
