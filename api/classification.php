<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__, 2) . DIRECTORY_SEPARATOR);
}

// Allowed request types
define('ACCESS_CONTROL_ALLOW_METHODS', array('GET', 'PUT', 'POST', 'DELETE'));

// Require user authentication
define('REQUIRE_AUTH', true);

require_once ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'init.php';

// Get classifications
if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    try {
        $request_params = array(
            'direction' => request::body('direction', false, true, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^(asc|desc)$/"))),
            'order' => request::body('order', false),
            'page' => request::body('page', false, false, FILTER_VALIDATE_INT),
            'per_page' => request::body('per_page', false, false, FILTER_VALIDATE_INT)
            
        );
        $request_data = array(
            'active' => request::query_str('active', false, false, FILTER_VALIDATE_BOOL),
            'label' => request::query_str('label', false),
            'id' => request::query_str('identifier', false, false, FILTER_VALIDATE_INT)
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

// Modify existing classifications
} elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    try {
        $request_params = array(
            'id' => request::query_str('identifier', true)
        );
        $request_data = array(
            'label' => request::body('label', false),
            'active' => request::body('active', false)
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

    try {
        db::init(USER_ID)->run_query("UPDATE classification SET label=?, active=? WHERE classificationID=?", "ssi", $request_data['label'], $request_data['active'], $request_params['id']);
        db::init(USER_ID)->run_query("SELECT * FROM classification WHERE  classificationID=?", "i", $request_params['id']);
        response::result(db::init(USER_ID)->fetch_one());
    } catch (exception_sys_link $e) {
        trigger_error("#" . $e->getCode() . " - " . $e->getMessage(), E_USER_ERROR);

        response::error('Internal application error occurred.');
        response::send(false, 500);
        exit;
    } catch (exception_usr_link $e) {
        trigger_error('uid: ' . USER_ID . " #" . $e->getCode() . " - " . $e->getMessage(), E_USER_NOTICE);

        response::error("Error with user database occoured. MySQL said: #" . $e->getCode() . " - " . $e->getMessage(), $e->getCode());
        response::send(false, 502);
        exit;
    }

    response::send(true, 200);
    exit;

// Create new classification
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    response::send(false, 501);
    exit;

// Delete classification
} elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    response::send(false, 501);
    exit;
}