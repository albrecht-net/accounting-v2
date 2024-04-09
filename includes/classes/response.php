<?php
class response {
    /**
     * @var array      $_response      Array contain data for response after form submit
     *                                 $_response = [
     *                                   'error'       => [
     *                                     'code'      => (integer),
     *                                     'message'   => (string)
     *                                   ],
     *                                   'result'      => [] (array)
     *                                   'result_info' => [
     *                                     'count'     => (integer),
     *                                     'total'     => (integer),
     *                                     'page'      => (integer),
     *                                     'per_page'  => (integer)
     *                                   ],
     *                                   'success'     => (bool)
     *                                 ]
     * 
     */
    private static $_response = array(
        'error' => array(),
        'result' => array(),
        'result_info' => array(),
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
    public static function send(bool $success, int $http_code, string ...$headers) {
        self::$_response['success'] = $success;

        http_response_code($http_code);
        
        if (!empty($headers)) {
            foreach ($headers as $value) {
                header($value);
            }
        }
        
        header('Content-Type: application/json');

        $_flags = 0;

        if (config::get('response.json.pretty') == true) {
            $_flags += JSON_PRETTY_PRINT;
        }

        echo json_encode(self::$_response, $_flags);

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
     * Add result info to response
     * 
     * @param integer  $count          Total number of results for the requested service
     * @param integer  $total          Total results available without any search parameters
     * @param integer  $page           Optional. Current page within paginated list of results
     * @param integer  $per_page       Optional. Number of results per page of results
     * @return void                    No value is returned
     */
    public static function result_info(int $count, int $total, int $page = null, int $per_page = null) {
        self::$_response['result_info']['count'] = $count;
        self::$_response['result_info']['total'] = $total;

        // Set page counter only if page num and entries per page where given
        if  (($page != null) && ($per_page != null)) {
            self::$_response['result_info']['page'] = $page;
            self::$_response['result_info']['per_page'] = $per_page;
        } else {
            self::$_response['result_info']['page'] = null;
            self::$_response['result_info']['per_page'] = null;
        }

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
        self::$_response['error']['code'] = $code;
        self::$_response['error']['message'] = $message;
    }
}