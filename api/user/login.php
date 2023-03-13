<?php
// Allowed request types
$_headerAllowMethod = array('POST');


if(in_array($_SERVER['REQUEST_METHOD'], $_headerAllowMethod)) {
    session_start();
    header('Content-Type: application/json');
    $_response = array(
        'success' => false,
        'message' => array(
            'messageBarType' => 2,
            'messageBarDismisible' => true,
            'messageTitle' => 'Tiitel',
            'messageBody' => session_id()
        ),
        'data' => $_POST,
        'cookie' => $_COOKIE
    );
    
    echo json_encode($_response);

} else {
    http_response_code(405);
    header('Allow: POST');
}