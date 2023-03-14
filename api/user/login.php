<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__, 3) . DIRECTORY_SEPARATOR);
}
require_once ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'init.php';

// Allowed request types
$access_control_allow_methods = array('POST');

// Verify if requested method is in allowed ones
if(!in_array($_SERVER['REQUEST_METHOD'], $access_control_allow_methods)) {
    http_response_code(405);
    header('Allow: ' . implode(', ', $access_control_allow_methods));
}


header('Content-Type: application/json');
$response = array(
    'success' => false,
    'message' => array(
        'messageBarType' => 2,
        'messageBarDismisible' => true,
        'messageTitle' => 'Tiitel'
    ),
    'data' => $_POST,
    'cookie' => $_COOKIE
);

echo json_encode($response);
