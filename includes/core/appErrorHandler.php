<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__, 3) . DIRECTORY_SEPARATOR);
}

// Overwrite display_errors from php.ini to prevent error output
ini_set('display_errors', false);

// Overwrite error_log from php.ini if path for log is given by in the config
if (!empty(config::get('log.path'))) {
    ini_set('error_log', config::get('log.path'));
}

// Overwrite error_reporting from php.ini if error_reporting level is given by in the config
if (!empty(config::get('log.level'))) {
    ini_set('error_reporting', intval(config::get('log.level')));
}

/**
 * Function error handler
 * Format errors with a sverity of 'E_USER_ERROR', 'E_USER_WARNING' and 'E_USER_NOTICE'
 * 
 * @param int $errno contains the level of the error raised, as an integer.
 * @param string $errstr contains the error message, as a string.
 * @param string $errfile contains the filename that the error was raised in, as a string.
 * @param int $errline contains the line number the error was raised at, as an integer.
 * @return bool Return false if error code is not included in error_reporting, so it use the standard PHP error handler. For other error codes return true.
 */
function appErrorHandler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    switch ($errno) {
        case E_USER_ERROR:
            $_errnostr = 'PHP Fatal error';
            break;

        case E_USER_WARNING:
            $_errnostr = 'PHP Warning';
            break;

        case E_USER_NOTICE:
            $_errnostr = 'PHP Notice';
            break;

        default:
            $_errnostr = $errno;
            break;
    }

    // Format error message string
    $msg = $_errnostr . ':  ' . $errstr;

    error_log($msg, 0);
    echo $msg;

    return true;
}

// Set error custom handler for errors with a sverity of 'E_USER_ERROR', 'E_USER_WARNING' and 'E_USER_NOTICE'
set_error_handler('appErrorHandler', E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE);