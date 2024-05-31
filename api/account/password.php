<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__, 3) . DIRECTORY_SEPARATOR);
}

// Allowed request types
define('ACCESS_CONTROL_ALLOW_METHODS', array('PUT'));

// Require user authentication
define('REQUIRE_AUTH', true);

require_once ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'init.php';

// Change login password
if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    try {
        $request_body = array(
            'password_old' => request::body('password_old', true, false),
            'password_new' => request::body('password_new', true, false),
        );

        // Query users table by given username
        db::init()->run_query("SELECT `password` FROM `users` WHERE id=? AND `status`='Y'", "i", USER_ID);

        if (db::init()->count() != 1) {
            trigger_error("No entry for user with id '" . USER_ID . "' was found in  users table during password change request.", E_USER_NOTICE);
            response::error('Internal application error occurred.');
            response::send(false, 500);
            exit;
        }
        
        if (!password_verify($request_body['password_old'], db::init()->fetch_one()['password'])) {
            response::error('Old password is incorrect.');
            response::send(false, 400);
            exit;
        }
        
        $time_now = time();
        
        // Set expire by date
        if ($request_body['remember']) {
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
        db::init()->run_query("INSERT INTO `sessions` (`id`, `user_id`, `user_agent`, `ip_address`, `expiry_date`, `last_activity`) VALUES (?, ?, ?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?))", "sissii", $sid, db::init()->fetch_one()['id'], $user_agent, $_SERVER['REMOTE_ADDR'], $time_expire, $time_now);
        
        // Send cookie with additional parameters
        setcookie('sid', $sid, $arr_cookie_options);
    
        response::result(array('session_id' => $sid));

    } catch (JsonException $e) {
        response::error('Faulty request data. JSON ' . $e->getMessage());
        response::send(false, 400);
        exit;
    } catch (RequestException | Exception $e) {
        response::error($e->getMessage());
        response::send(false, 400);
        exit;
    } catch (exception_sys_link $e) {
        trigger_error("#" . $e->getCode() . " - " . $e->getMessage(), E_USER_ERROR);
    
        response::error('Internal application error occurred.');
        response::send(false, 500);
        exit;
    }

    response::send(true, 200);
    exit;
}
