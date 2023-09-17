<?php
class response {
    /**
     * @var array      $_response      Array contain data for response after form submit
     *                                 $_response = [
     *                                   'error' => [
     *                                     'code'    => (integer)
     *                                     'message'     => (string)
     *                                   ]
     *                                   'result'              => [] (array)
     *                                   'success'           => (bool)
     *                                 ]
     * 
     */
    private static $_response = array(
        'error' => array(),
        'result' => array(),
        'success' => null
    );

    /**
     * Send response
     * 
     * @param bool     $success        Whether the API call was successful.
     * @param integer  $http_code      Set the HTTP response code.
     * @param string   $header         Optional. Send a raw HTTP header.
     * @param string   $headers        Optional. Additional raw HTTP header.
     * @return void                    No value is returned
     */
    public static function send(bool $success, int $http_code, string ...$headers = null) {
        self::$_response['success'] = $success;

        http_response_code($http_code);

        if (isset($headers)) {
            foreach ($headers as $value) {
                header($value);
            }
        }

        return;
    }

    /**
     * Add data as array to response
     * 
     * @param array    $data           Merge data to one array
     * @return void                    No value is returned
     */
    public static function result(array $data) {
        self::$_response['result'] = array_merge(self::$_response['result'], $data);

        return;
    }

    /**
     * Add error data to response
     * 
     * @param string   $message        Error message.
     * @param integer  $code           Optional. Set error code.
     * @return void                    No value is returned
     */
    public static function error(string $message, int $code = null) {

    }
}