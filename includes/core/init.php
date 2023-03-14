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

// session::start();

require_once ROOT_PATH .'includes' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'appErrorHandler.php';