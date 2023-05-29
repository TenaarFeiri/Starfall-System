<?php
    function my_autoloader($class_name) {
        // Define the base directory for our classes.
        $base_dir = __DIR__ . '/';
        // Replace the namespace separator with directory separator.
        $file = str_replace('\\', '/', $class_name) . '.php';
        // Build the path to the class file.
        $path = $base_dir . $file;
        if(DEBUG)
        {
            echo "Autoloader: " . $path . "\n";
        }
        // If the file exists, require it.
        if (file_exists($path)) {
            require $path;
        }
    }
    // Register the autoloader function.
    spl_autoload_register('my_autoloader');

?>