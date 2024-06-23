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

        if ($request_body['password_old'] == $request_body['password_new']) {
            throw new ApplicationRuntimeException('Old password cannot be the same as the new one.');
        }

        if (strlen($request_body['password_new']) < 1) {
            throw new ApplicationRuntimeException('New password cannot be an empty string.');
        }

        // Query users table by given username
        db::init()->run_query("SELECT `password` FROM `users` WHERE id=? AND `status`='Y'", "i", USER_ID);

        if (db::init()->num_rows() != 1) {
            trigger_error("No entry for user with id '" . USER_ID . "' was found in  users table during password change request.", E_USER_NOTICE);
            response::error('Internal application error occurred.');
            response::send(false, 500);
            exit;
        }
        
        if (!password_verify($request_body['password_old'], db::init()->fetch_one()['password'])) {
            throw new ApplicationRuntimeException('Old password is incorrect.');
        }

        // Update password in users table
        db::init()->run_query("UPDATE `users` SET `password`=? WHERE `id`=?", "si", password_hash($request_body['password_new'], PASSWORD_DEFAULT), USER_ID);

        // Invalidate all active user sessions
        db::init()->run_query("UPDATE `sessions` SET `expiry_date` = CURRENT_TIMESTAMP() WHERE `user_id`=? and `expiry_date` > NOW()", "i", USER_ID);
    } catch (JsonException $e) {
        response::error('Faulty request data. JSON ' . $e->getMessage(), $e->getCode());
        response::send(false, 400);
        exit;
    } catch (RequestException | ApplicationRuntimeException $e) {
        response::error($e->getMessage(), $e->getCode());
        response::send(false, 400);
        exit;
    } catch (ValueError $e) {
        trigger_error("PasswordChange #" . $e->getCode() . " - UID: "  . USER_ID . " - " . $e->getMessage(), E_USER_NOTICE);
        response::error('Internal application error occurred.');
        response::send(false, 500);
        exit;
    } catch (DbSysLinkException $e) {
        response::error('Internal application error occurred.');
        response::send(false, 500);
        exit;
    }

    response::send(true, 205);
    exit;
}
