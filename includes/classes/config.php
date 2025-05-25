<?php
class config {
    /**
     * @var array $_config Contains array from config file after load.
     */
    private static $_config = null;

    /**
     * Load config.php file
     * 
     * @return void Retrun void if including of config.php was successful otherwise output a http error 500.
     */
    private static function load() {
        self::$_config = include_once ROOT_PATH . 'conf' . DIRECTORY_SEPARATOR . 'config.php';

        // If config.php cannot get included
        if (self::$_config === false) {
            trigger_error('No configuration file found in ' . ROOT_PATH . 'conf' . DIRECTORY_SEPARATOR . 'config.php', E_USER_ERROR);
        }

        return;
    }

    /**
     * Get config value
     * 
     * @param string $name Must be a name of a config array key.
     * @return mixed|void Configuration value, or void if name not found.
     */
    public static function get(string|null $name = null) {
        if ($name != null) {
            if (self::$_config == null) {
                self::load();
            }

            return self::$_config[$name];
        }

        return;
    }
}