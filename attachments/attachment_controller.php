<?php
    // First we create our root path as a constant. We will have no way of knowing the exact path, so use a magic variable.
    const ROOT_PATH = __DIR__ . '/..';
    require_once ROOT_PATH . '/classes/autoloader.php';
    // Before all else, set us to full plaintext.
    header("Content-Type: text/plain");

    // Then enable all PHP errors and warnings.
    error_reporting(E_ALL);

    // Get the headers sent from the Second Life client.
    $headers = getallheaders();
    
    // Then we set up our debug constant.
    const DEBUG = true; // False for production, true for development.

    // Now, we check the debug condition.
    if(!DEBUG)
    {
        // If we are in debug, then we don't hard check for the headers we want,
        // but while it's false, we do.
        $arr_key_check = array("X-SecondLife-Shard", "X-SecondLife-Region", "X-SecondLife-Owner-Name", "X-SecondLife-Owner-Key", "X-SecondLife-Object-Key");
        foreach($arr_key_check as $key)
        {
            if(!isset($headers[$key]))
            {
                die("error::You are not allowed to access this page.");
            }
        }
    }
    
    // Now let's just create the PDO object.
    // We will pass it around to any of the classes that need it.
    // Our database file is out of the public facing scope. We'll need to include it, but we'll
    // also need to work out the path for it. It should be one directory above this one.
    require_once(ROOT_PATH."/../database.php");

    // With the database file included, make the PDO!
    // The file has a handy function for this called connectToStarfall().
    $database = new Database();
    $pdo = $database->connectToStarfall();

    // Verify a good connection.
    if(!$pdo)
    {
        die("error::Could not connect to the database.");
    }
    $attachment = new attacher\MakeAttachment($pdo);
    
?>