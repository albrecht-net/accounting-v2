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
     * @param integer  $filter         Optional. The ID of the filter to apply before return value. If omitted, FILTER_DEFAULT will be used,
     *                                 which is equivalent to FILTER_UNSAFE_RAW.
     * @param array|int $options       Optional. Associative array of options or bitwise disjunction of flags.
     *                                 The default value provided in ['options']['default'] will not overwrite the value if the filter fails.
     * @throws                         exception_request if the query parameter with $name is required but was not found.
     * @return mixed|void              Query string value, or void if name not found.
     */
    public static function query_str($name, $required, $trim = true, $filter = FILTER_DEFAULT, $options = array()) {
        if (count(self::$_query_str) == 0) {
            self::load_query_str();
        }

        // Filter value if one is provied in query string
        if (isset(self::$_query_str[$name])) {
            $_request = self::$_query_str[$name];

            if ($trim) {
                $_request = trim($_request);
            }

            // Add FILTER_NULL_ON_FAILURE flag as option if filter type is set to FILTER_VALIDATE_BOOL
            // Overwrite default value to catch filter failures
            if ($filter === FILTER_VALIDATE_BOOL) {
                $options['flags'] |= FILTER_NULL_ON_FAILURE;
                $options['options']['default'] = null;
            } else {
                $options['options']['default'] = false;
            }

            $_request = filter_var($_request, $filter, $options);

            if (($_request === false && $filter !== FILTER_VALIDATE_BOOL) || ($_request === null && $filter === FILTER_VALIDATE_BOOL)) {
                throw new exception_request("Query parameter \"" . $name . "\" does not match required format.");
            }

        // Throw exception if value was not set but is required
        } elseif ($required) {
            throw new exception_request("Query parameter \"" . $name . "\" was not provided in URL but is required.");

        // Return default value if value was not set
        } elseif (isset($options['options']['default'])) {
            $_request = $options['options']['default'];

        // Return void if value was not set, not required and no default value was provided
        } else {
            $_request = null;
        }

        return $_request;
    }

    /**
     * Get value from request body
     * 
     * @param string   $name           Name of a value in the request body
     * @param bool     $required       Throws a exception if set to true, but no value was found in the query.
     * @param bool     $trim           Optional. Strip whitespace from the beginning and end before return query value.
     * @param integer  $filter         Optional. The ID of the filter to apply before return value. If omitted, FILTER_DEFAULT will be used,
     *                                 which is equivalent to FILTER_UNSAFE_RAW.
     * @param array|int $options       Optional. Associative array of options or bitwise disjunction of flags.
     *                                 The default value provided in ['options']['default'] will not overwrite the value if the filter fails.
     * @throws                         exception_request if the query parameter with $name is required but was not found.
     * @return mixed|void              Request body value, or void if name not found.
     */
    public static function body($name, $required, $trim = true, $filter = FILTER_DEFAULT, $options = 0) {
        if (count(self::$_body) == 0) {
            self::load_body();
        }

        // Filter value if one is provied in request body
        if (isset(self::$_body[$name])) {
            $_request = self::$_body[$name];

            if ($trim) {
                $_request = trim($_request);
            }

            // Add FILTER_NULL_ON_FAILURE flag as option if filter type is set to FILTER_VALIDATE_BOOL
            // Overwrite default value to catch filter failures
            if ($filter === FILTER_VALIDATE_BOOL) {
                $options['flags'] |= FILTER_NULL_ON_FAILURE;
                $options['options']['default'] = null;
            } else {
                $options['options']['default'] = false;
            }

            $_request = filter_var($_request, $filter, $options);

            if (($_request === false && $filter !== FILTER_VALIDATE_BOOL) || ($_request === null && $filter === FILTER_VALIDATE_BOOL)) {
                throw new exception_request("Query parameter \"" . $name . "\" does not match required format.");
            }

        // Throw exception if value was not set but is required
        } elseif ($required) {
            throw new exception_request("Query parameter \"" . $name . "\" was not provided in request body but is required.");

        // Return default value if value was not set
        } elseif (isset($options['options']['default'])) {
            $_request = $options['options']['default'];

        // Return void if value was not set, not required and no default value was provided
        } else {
            $_request = null;
        }

        return $_request;
    }
}