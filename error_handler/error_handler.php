<?php
    function showErrors($errno, $errstr, $errfile, $errline)
    {
        date_default_timezone_set('Europe/Oslo');
        $timestamp = date('Y-m-d H:i:s');
        $archivestamp = date('YmdHis');
        $max_lines = 10000;
        $max_size = 900 * 1024 * 1024; // 900 MB.
        $max_logs = 1000;
        $max_dir_size = 1024 * 1024 * 1024; // 1 GB.
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
        $log_file = __DIR__ . '/logs/errors.log';
        $num_lines = count(file($log_file));
        $file_size = filesize($log_file);
        if($num_lines >= $max_lines || $file_size >= $max_size)
        {
            $archive_dir = __DIR__ . '/logs/archived';
            if(!is_dir($archive_dir))
            {
                if(!mkdir($archive_dir))
                {
                    echo "Could not create archive directory. (mkdir())" , PHP_EOL;
                    exit();
                }
                // Verify that dir exists.
                if(!is_dir($archive_dir))
                {
                    echo "Could not create archive directory. (is_dir())" , PHP_EOL;
                    exit();
                }
                if(!chmod($archive_dir, 0777))
                {
                    echo "Could not set permissions on archive directory." , PHP_EOL;
                    exit();
                }
            }
            $archive_file = $archive_dir . '/errors_' . $archivestamp . '.log';
            $logs = glob("$archive_dir/*.log"); // Get all the logs from the archive directory.
            $num_logs = count($logs); // Count them.
            $dir_size = array_sum(array_map('filesize', $logs)); // Get the size of all the logs.
            if($num_logs >= $max_logs || $dir_size >= $max_dir_size) // If there are too many logs or the directory is too big.
            {
                // Sort the logs by mod time, oldest first.
                usort($logs, function($a, $b) {
                    return filemtime($a) - filemtime($b);
                });

                // Then delete the oldest logs until the maximum num of logs or dir size reached.
                while (($num_logs >= $max_logs || $dir_size >= $max_dir_size) and !empty($logs)) 
                {
                    $oldest_log = array_shift($logs);
                    if(unlink($oldest_log))
                    {
                        $dir_size -= filesize($oldest_log);
                    }
                    $num_logs--;
                    // Check if $logs is empty, break if it is.
                    if (empty($logs)) 
                    {
                        break;
                    }
                }
            }
            rename($log_file, $archive_file); // Actually move the file.
            if(!is_file($archive_file))
            {
                echo "Could not archive log file." , PHP_EOL;
                exit();
            }
        }
        if(!error_log("[$timestamp] Error: [$error_level] $errstr in $errfile on line $errline" . PHP_EOL, 3, __DIR__ . '/logs/errors.log'))
        {
            echo "Could not log to file." , PHP_EOL;
        }
    
        // Display a user-friendly error message
        if(DEBUG)
        {
            echo "[$timestamp] Error: [$error_level] $errstr in $errfile on line $errline" . PHP_EOL;
        }
        echo "err:An error occurred. Please try again later.";

    
        // Stop the script from executing further
        exit(1);
    }
?>