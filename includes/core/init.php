<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__, 3) . DIRECTORY_SEPARATOR);
}

/**
 * Function class autoloader
 * Include class file given by name.
 * 
 * @param string $class_name Name of class that should get included.
 * @return bool Return true on success or false on failure.
 */
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
require_once ROOT_PATH .'includes' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'userAuthenticate.php';

// Verify if requested method is in allowed ones
if(!in_array($_SERVER['REQUEST_METHOD'], ACCESS_CONTROL_ALLOW_METHODS)) {
    http_response_code(405);
    header('Allow: ' . implode(', ', ACCESS_CONTROL_ALLOW_METHODS));
    exit();
}
