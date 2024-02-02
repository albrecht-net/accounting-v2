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
     * @param integer  $filter         Optional. The ID of the filter to apply before return value. If omitted, FILTER_DEFAULT will be used, which is equivalent to FILTER_UNSAFE_RAW.
     * @param array|int $options       Optional. Associative array of options or bitwise disjunction of flags.
     * @throws                         exception_request if the query parameter with $name is required but was not found.
     * @return mixed|void              Query string value, or void if name not found.
     */
    public static function query_str($name, $required, $trim = true, $filter = FILTER_DEFAULT, $options = 0) {
        if (count(self::$_query_str) == 0) {
            self::load_query_str();
        }

        $_req = self::$_query_str[$name];

        if (isset($_req)) {
            if ($trim) {
                $_req = trim($_req);
            }

            $_req = filter_var($_req, $filter, $options);

            if (($_req === false && $filter !== FILTER_VALIDATE_BOOL) || ($_req === null && $filter === FILTER_VALIDATE_BOOL)) {
                throw new exception_request("Query parameter \"" . $name . "\" does not match required format.");
            } else {
                return $_req;
            }

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
     * @param integer  $filter         Optional. The ID of the filter to apply before return value. If omitted, FILTER_DEFAULT will be used, which is equivalent to FILTER_UNSAFE_RAW.
     * @param array|int $options       Optional. Associative array of options or bitwise disjunction of flags.
     * @throws                         exception_request if the query parameter with $name is required but was not found.
     * @return mixed|void              Request body value, or void if name not found.
     */
    public static function body($name, $required, $trim = true, $filter = FILTER_DEFAULT, $options = 0) {
        if (count(self::$_body) == 0) {
            self::load_body();
        }

        $_req = self::$_body[$name];

        if (isset($_req)) {
            if ($trim) {
                $_req = trim($_req);
            }

            $_req = filter_var($_req, $filter, $options);

            if (($_req === false && $filter !== FILTER_VALIDATE_BOOL) || ($_req === null && $filter === FILTER_VALIDATE_BOOL)) {
                throw new exception_request("Query parameter \"" . $name . "\" does not match required format.");
            } else {
                return $_req;
            }

        } elseif ($required) {
            throw new exception_request("Query parameter \"" . $name . "\" was not provided in request body but is required.");
        }

        return;
    }
}