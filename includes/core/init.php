<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__, 3) . DIRECTORY_SEPARATOR);
}

// Autoload classes
spl_autoload_register(function ($class_name) {

    $path = ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;
    $extension = '.php';
    $file = $path . $class_name . $extension;
    
    if (!file_exists($file)) {
        return false;
    } else {
        require_once $file;
        return true;
    }
});

require_once ROOT_PATH .'includes' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'appErrorHandler.php';

// Verify if requested method is in allowed ones
if(!in_array($_SERVER['REQUEST_METHOD'], ACCESS_CONTROL_ALLOW_METHODS)) {
    response::error('Method ' . $_SERVER['REQUEST_METHOD'] . ' not available for that URI.');
    response::send(false, 405, 'Allow: ' . implode(', ', ACCESS_CONTROL_ALLOW_METHODS));
    exit;
}

// Verify request content-type header. If value is set, only application/json is allowed.
if (!empty($_SERVER['CONTENT_TYPE'])) {
    if (trim(explode(';', $_SERVER['CONTENT_TYPE'])[0]) !== 'application/json') {
        response::error('Invalid Content-Type header, valid values are application/json');
        response::send(false, 415, 'Content-Type: application/json');
        exit;
    }    
}

require_once ROOT_PATH .'includes' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'userAuthenticate.php';
