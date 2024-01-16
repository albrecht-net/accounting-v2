<?php
class exception_request extends Exception {}
class request {
    /**
     * @var array      $_query_str     Array with values from request query string
     */
    private static $_query_str = array();

    /**
     * @var array      $_body          Array with values from request body
     */
    private static $_body = array();

    /**
     * Load request query string
     * 
     * @return void                    No value is returned
     */
    private static function load_query_str() {
        self::$_query_str = $_GET;
        return;
    }

    /**
     * Load request body values
     * 
     * @throws                         JsonException if json in request body is invalid
     * @return void                    No value is returned
     */
    private static function load_body() {
        $_body_raw = file_get_contents('php://input');

        if (!empty($_body_raw)) {
            self::$_body = json_decode($_body_raw, true, 512, JSON_THROW_ON_ERROR);
        }
        return;
    }

    /**
     * Get value from query string
     * 
     * @param string   $name           Name of a value in the query string
     * @param bool     $required       Throws a exception if set to true, but no value was found in the query.
     * @param bool     $trim           Optional. Strip whitespace from the beginning and end before return query value.
     * @throws                         exception_request if the query parameter with $name is required but was not found.
     * @return mixed|void              Query string value, or void if name not found.
     */
    public static function query_str($name, $required, $trim = true) {
        if (count(self::$_query_str) == 0) {
            self::load_query_str();
        }

        if (isset(self::$_query_str[$name]) && $trim) {
            return trim(self::$_query_str[$name]);
        } elseif ((isset(self::$_query_str[$name]))) {
            return self::$_query_str[$name];
        } elseif ($required) {
            throw new exception_request("Query parameter \"" . $name . "\" was not provided in URL but is required.");
        }

        return;
    }

    /**
     * Get value from request body
     * 
     * @param string   $name           Name of a value in the request body
     * @param bool     $required       Throws a exception if set to true, but no value was found in the query.
     * @param bool     $trim           Optional. Strip whitespace from the beginning and end before return query value.
     * @throws                         exception_request if the query parameter with $name is required but was not found.
     * @return mixed|void              Request body value, or void if name not found.
     */
    public static function body($name, $required, $trim = true) {
        if (count(self::$_body) == 0) {
            self::load_body();
        }

        if (isset(self::$_body[$name]) && $trim) {
            return self::$_body[$name];
        } elseif ((isset(self::$_body[$name]))) {
            return self::$_body[$name];
        } elseif ($required) {
            throw new exception_request("Query parameter \"" . $name . "\" was not provided in request body but is required.");
        }

        return;
    }
}