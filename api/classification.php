<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__, 3) . DIRECTORY_SEPARATOR);
}

// Allowed request types
define('ACCESS_CONTROL_ALLOW_METHODS', array('GET', 'PATCH', 'POST', 'DELETE'));

// Require user authentication
define('REQUIRE_AUTH', true);

require_once ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'init.php';

// Get classifications
if ($_SERVER['REQUEST_METHOD'] == 'GET') {

// Modify existing classifications
} elseif ($_SERVER['REQUEST_METHOD'] == 'PATCH') {

// Create new classification
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {

// Delete classification
} elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
}