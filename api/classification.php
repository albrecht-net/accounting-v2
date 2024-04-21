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
        $path_parameters = array(
            'id' => request::query_str('identifier', false, false, FILTER_VALIDATE_INT)
            
        );
        $query_parameters = array(
            'active' => request::query_str('active', false, false, FILTER_VALIDATE_BOOL),
            'direction' => request::body('direction', false, true, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^(asc|desc)$/"))),
            'label' => request::query_str('label', false),
            'label.contains' => request::query_str('label', false),
            'label.endswith' => request::query_str('label', false),
            'label.startswith' => request::query_str('label', false),
            'match' => request::body('match', false, true, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^(any|all)$/"))),
            'order' => request::body('order', false),
            'page' => request::body('page', false, false, FILTER_VALIDATE_INT),
            'per_page' => request::body('per_page', false, false, FILTER_VALIDATE_INT),
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

    // Query classifications
    try {
        $query = "SELECT * FROM `classification`";

        switch (true) {
            case (isset($path_parameters['id'])):
                $sql_conditions = "`classificationID`=?";
                $sql_parameters[0] .= "i";
                $sql_parameters[] = $path_parameters['id'];
                break;

            case ($query_parameters['active'] === true):
                $sql_conditions = "``active`='Y'";

            case ($query_parameters['active'] === false):
                $sql_conditions = "``active`='N'";

            case (isset($query_parameters['label'])):
                $sql_conditions = "`label`=?";
                $sql_parameters[0] .= "s";
                $sql_parameters[] = $query_parameters['label'];

            case (isset($query_parameters['label.contains'])):
                $sql_conditions = "`label`=?";
                $sql_parameters[0] .= "s";
                $sql_parameters[] = "%" . $query_parameters['label.contains'] . "%";

        }

        response::result(db::init(USER_ID)->fetch_array());
        response::result_info(db::init(USER_ID)->count(), -1);
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

// Modify existing classifications
} elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    try {
        $path_parameters = array(
            'id' => request::query_str('identifier', true)
        );
        $request_body = array(
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
        db::init(USER_ID)->run_query("UPDATE classification SET label=?, active=? WHERE classificationID=?", "ssi", $request_body['label'], $request_body['active'], $path_parameters['id']);
        db::init(USER_ID)->run_query("SELECT * FROM classification WHERE  classificationID=?", "i", $path_parameters['id']);
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