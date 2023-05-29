<?php
    function showErrors($errno, $errstr, $errfile, $errline)
    {
        date_default_timezone_set('Europe/Oslo');
        $timestamp = date('Y-m-d H:i:s');
        // Log the error to a file or database
        switch ($errno) {
            case E_ERROR:
                $error_level = 'E_ERROR';
                break;
            case E_WARNING:
                $error_level = 'E_WARNING';
                break;
            case E_PARSE:
                $error_level = 'E_PARSE';
                break;
            case E_NOTICE:
                $error_level = 'E_NOTICE';
                break;
            case E_CORE_ERROR:
                $error_level = 'E_CORE_ERROR';
                break;
            case E_CORE_WARNING:
                $error_level = 'E_CORE_WARNING';
                break;
            case E_COMPILE_ERROR:
                $error_level = 'E_COMPILE_ERROR';
                break;
            case E_COMPILE_WARNING:
                $error_level = 'E_COMPILE_WARNING';
                break;
            case E_USER_ERROR:
                $error_level = 'E_USER_ERROR';
                break;
            case E_USER_WARNING:
                $error_level = 'E_USER_WARNING';
                break;
            case E_USER_NOTICE:
                $error_level = 'E_USER_NOTICE';
                break;
            case E_STRICT:
                $error_level = 'E_STRICT';
                break;
            case E_RECOVERABLE_ERROR:
                $error_level = 'E_RECOVERABLE_ERROR';
                break;
            case E_DEPRECATED:
                $error_level = 'E_DEPRECATED';
                break;
            case E_USER_DEPRECATED:
                $error_level = 'E_USER_DEPRECATED';
                break;
            default:
                $error_level = "Unknown error level ($errno)";
                break;
        }
        error_log("[$timestamp] Error: [$error_level] $errstr in $errfile on line $errline" . PHP_EOL, 3, __DIR__ . '/logs/errors.log');
    
        // Display a user-friendly error message
        echo "err:An error occurred. Please try again later.";

    
        // Stop the script from executing further
        exit(1);
    }
?>