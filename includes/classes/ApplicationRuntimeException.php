<?php
// Runtime exception which additionally pushes exception messages to the logfile
class ApplicationRuntimeException extends RuntimeException {
    public function __construct(string $message = "", int $code = 0, throwable|null $previous = null) {
        trigger_error("ApplicationRuntimeException #" . $code . " - UID: "  . USER_ID . " - " . $message, E_USER_NOTICE);
        parent::__construct($message, $code, $previous);
    }
}