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
            'id' => request::query_str('id', false, false, FILTER_VALIDATE_INT)
        );
        $query_parameters = array(
            'active' => request::body('active', false, false, FILTER_VALIDATE_BOOL),
            'direction' => request::body('direction', false, true, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^(asc|desc)$/i", 'default' => 'asc'))),
            'label' => request::body('label', false, true),
            'label.contains' => request::body('label.contains', false),
            'label.endswith' => request::body('label.endswith', false),
            'label.startswith' => request::body('label.startswith', false),
            'match' => request::body('match', false, true, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^(any|all)$/", 'default' => 'all'))),
            'order' => request::body('order', false, true, FILTER_DEFAULT, array('options' => array('default' => 'classificationID'))),
            'page' => request::body('page', false, false, FILTER_VALIDATE_INT, array('options' => array('default' => 1))),
            'per_page' => request::body('per_page', false, false, FILTER_VALIDATE_INT, array('options' => array('default' => 100))),
        );

        // Base query
        $query = "SELECT * FROM `classification`";

        // Where conditions
        $sql_conditions = [];
        $sql_parameters = [''];

        if (isset($path_parameters['id'])) {
            $sql_conditions[] = "`classificationID`=?";
            $sql_parameters[0] .= "i";
            $sql_parameters[] = $path_parameters['id'];
        } else {
            if ($query_parameters['active'] === true) {
                $sql_conditions[] = "`active`='Y'";
            } elseif ($query_parameters['active'] === false) {
                $sql_conditions[] = "`active`='N'";
            }

            if (isset($query_parameters['label'])) {
                $sql_conditions[] = "`label`=?";
                $sql_parameters[0] .= "s";
                $sql_parameters[] = $query_parameters['label'];
            }

            if (isset($query_parameters['label.contains'])) {
                $sql_conditions[] = "`label` LIKE ?";
                $sql_parameters[0] .= "s";
                $sql_parameters[] = "%" . $query_parameters['label.contains'] . "%";
            }

            if (isset($query_parameters['label.endswith'])) {
                $sql_conditions[] = "`label` LIKE ?";
                $sql_parameters[0] .= "s";
                $sql_parameters[] = "%" . $query_parameters['label.endswith'];
            }

            if (isset($query_parameters['label.startswith'])) {
                $sql_conditions[] = "`label` LIKE ?";
                $sql_parameters[0] .= "s";
                $sql_parameters[] = $query_parameters['label.startswith'] . "%";
            }
        }

        if (!empty($sql_conditions)) {
            switch ($query_parameters['match']) {
                case ("any"):
                    $query .= " WHERE " . implode(' OR ', $sql_conditions);
                    break;
                case ("all"):
                default:
                    $query .= " WHERE " . implode(' AND ', $sql_conditions);
                    break;
            }
        }

        // Order resultset
        $query .= " ORDER BY `" . $query_parameters['order'] . "` " . strtoupper($query_parameters['direction']);

        // Pagination of resultset if page and per_page parameters are > 0
        if (($query_parameters['page'] > 0) and ($query_parameters['per_page'] > 0)) {
            $_offset = ($query_parameters['page'] - 1) * $query_parameters['per_page'];
            $_row_count = $query_parameters['per_page'];
            $query .= " LIMIT " . $_offset . ", " . $_row_count;
        }

        db::init(USER_ID)->run_query($query, $sql_parameters[0], ...array_slice($sql_parameters, 1));

        response::result(db::init(USER_ID)->fetch_array());
        response::result_info(db::init(USER_ID)->num_rows(), db::init(USER_ID)->num_rows_all('classification'), $query_parameters['page'], $query_parameters['per_page']);
    } catch (JsonException $e) {
        response::error('Faulty request data. JSON ' . $e->getMessage(), $e->getCode());
        response::send(false, 400);
        exit;
    } catch (RequestException $e) {
        response::error($e->getMessage(), $e->getCode());
        response::send(false, 400);
        exit;
    } catch (DbSysLinkException $e) {
        response::error('Internal application error occurred.');
        response::send(false, 500);
        exit;
    } catch (DbUsrLinkException $e) {
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
            'id' => request::query_str('id', true, false, FILTER_VALIDATE_INT)
        );
        $query_parameters = array(
            'active' => request::body('active', false, false, FILTER_VALIDATE_BOOL),
            'label' => request::body('label', false, true)
        );

        if (request::$query_param_count == 0) {
            throw new ApplicationRuntimeException('Provide at least one update parameter in request body.');
        }

        // Base query
        $query = "UPDATE `classification` SET";

        // Update conditions
        $sql_conditions = [];
        $sql_parameters = [''];

        if (isset($query_parameters['label'])) {
            $sql_conditions[] = "`label`=?";
            $sql_parameters[0] .= "s";
            $sql_parameters[] = $query_parameters['label'];
        }

        if ($query_parameters['active'] === true) {
            $sql_conditions[] = "`active`='Y'";
        } elseif ($query_parameters['active'] === false) {
            $sql_conditions[] = "`active`='N'";
        }

        if (!empty($sql_conditions)) {
            $query .= implode(', ', $sql_conditions);
        }

        $query .= " WHERE `classificationID`=?";
        $sql_parameters[0] .= "i";
        $sql_parameters[] = $path_parameters['id'];

        db::init(USER_ID)->run_query($query, $sql_parameters[0], ...array_slice($sql_parameters, 1));
        db::init(USER_ID)->run_query("SELECT * FROM `classification` WHERE `classificationID`=?", "i", $path_parameters['id']);

        response::result(db::init(USER_ID)->fetch_one());
    } catch (JsonException $e) {
        response::error('Faulty request data. JSON ' . $e->getMessage(), $e->getCode());
        response::send(false, 400);
        exit;
    } catch (RequestException | ApplicationRuntimeException $e) {
        response::error($e->getMessage(), $e->getCode());
        response::send(false, 400);
        exit;
    } catch (DbSysLinkException $e) {
        response::error('Internal application error occurred.');
        response::send(false, 500);
        exit;
    } catch (DbUsrLinkException $e) {
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
    try {
        $path_parameters = array(
            'id' => request::query_str('id', true, false, FILTER_VALIDATE_INT)
        );

        db::init(USER_ID)->run_query("DELETE FROM `classification` WHERE `classificationID`=?", "i", $path_parameters['id']);
    } catch (RequestException $e) {
        response::error($e->getMessage(), $e->getCode());
        response::send(false, 400);
        exit;
    } catch (DbSysLinkException $e) {
        response::error('Internal application error occurred.');
        response::send(false, 500);
        exit;
    } catch (DbUsrLinkException $e) {
        response::error("Error with user database occoured. MySQL said: #" . $e->getCode() . " - " . $e->getMessage(), $e->getCode());
        response::send(false, 502);
        exit;
    }

    response::send(true, 200);
    exit;
}