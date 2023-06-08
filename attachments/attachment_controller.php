<?php
    // Then we set up our debug constant.
    const DEBUG = false; // False for production, true for development.
    // First we create our root path as a constant. We will have no way of knowing the exact path, so use a magic variable.
    const ROOT_PATH = __DIR__ . '/..';
    $db_path = dirname(dirname(dirname(dirname(ROOT_PATH)))) . '/new_database.php';
    require_once ROOT_PATH . '/classes/autoloader.php';
    require_once ROOT_PATH . '/error_handler/error_handler.php';
    // Enable all PHP errors and warnings.
    error_reporting(E_ALL);
    set_error_handler('showErrors'); // Set our custom error handler.
    // Before all else, set us to full plaintext.
    header("Content-Type: text/plain");
    require_once($db_path); // Database.
    // Get the headers sent from the Second Life client.
    $headers = apache_request_headers();

    // Now, we check the debug condition.
    if(!DEBUG)
    {
        // If we are in debug, then we don't hard check for the headers we want,
        // but while it's false, we do.
        $arr_key_check = array("User-Agent", "X-SecondLife-Object-Key", "X-SecondLife-Object-Name", "X-SecondLife-Owner-Key");
        if($headers['X-SecondLife-Region'] != "Starfall Roleplay (130048, 351744)" or $headers['X-SecondLife-Shard'] != "Production")
        {
            die("err:You are not allowed to access this page.");
        }
        foreach($arr_key_check as $key)
        {
            if(!isset($headers[$key]))
            {
                die("err:You are not allowed to access this page.");
            }
        }
    }
    
    // Get the post data if we're not in debug mode. Otherwise, use get.
    if(!DEBUG)
    {
        $vars = $_POST;
    }
    else
    {
        $vars = $_GET;
    }

    /*
        $vars will always have an 'action' key, and this will not be empty. If there is none present, we throw an error and exit.
        Depending on the action, there may be other variables. As this is an attachment controller, we will likely only deal with
        a CSV of UUIDs, in the 'uuid' variable.
        However, we may also receive requests for redeliveries so we have to be able to handle that.
    */

    // First we validate that we have an action.
    if(!isset($vars['action']))
    {
        $error_message = "No action specified.";
        trigger_error($error_message, E_USER_ERROR);
    }
    if($vars['action'] === "attachmentRequest")
    {
        // We have an attachment request. Validate that we have the UUID variable.
        if(!isset($vars['uuid']) || empty($vars['uuid']))
        {
            $error_message = "attachmentRequest received but no UUID specified.";
            trigger_error($error_message, E_USER_ERROR);
        }
        else
        {
            // Validate that the UUID is a valid CSV.
            // As we know the input will ALWAYS be UUIDs, we'll do a regex that validates UUID format and that it's CSV compliant.
            if(!preg_match("/^([a-fA-F0-9]{8}(-[a-fA-F0-9]{4}){4}[a-fA-F0-9]{8})(,([a-fA-F0-9]{8}(-[a-fA-F0-9]{4}){4}[a-fA-F0-9]{8}))*$/", $vars['uuid']))
            {
                $error_message = "attachmentRequest received but UUID is not a valid CSV." . PHP_EOL . "UUIDs: " . $vars['uuid'];
                trigger_error($error_message, E_USER_ERROR);
            }
            else
            {
                $uuids = explode(",", $vars['uuid']); // Explode into an array!
            }
        }
        try
        {
            // Instantiate the MakeAttachment class.
            $attachment = new attacher\MakeAttachment();
            // And then issue the attachment request! This will create a new position in the queue if it doesn't already exist.
            $result = $attachment->prepareAttachments($uuids);
            if($result !== false)
            {
                echo $result;
            }
            else
            {
                if(DEBUG)
                {
                    echo "err:Attachment request failed.";
                }
            }
        }
        catch(Exception $e)
        {
            $trace = $e->getTrace();
            $trace_msg = "";
                foreach($trace as $item)
                {
                    $trace_msg .= $item['file'] . " on line " . $item['line'] . PHP_EOL;
                }
            $error_message = "MakeAttachment failure. Error: " . $e->getMessage() . PHP_EOL . "Trace: " . $trace_msg;
             // Kill the object before we throw the error.
            $attachment = null;
            trigger_error($error_message, E_USER_ERROR);
        }
        finally
        {
            $attachment = null;
        }
    }
    else if($vars['action'] === "redeliver")
    {
        if(!isset($vars['uuid']) or empty($vars['uuid']))
        {
            $error_message = "redeliver received but no UUID specified.";
            trigger_error($error_message, E_USER_ERROR);
        }
        else
        {
            try
            {
                // Validate UUID.
                if(!preg_match("/^([a-fA-F0-9]{8}(-[a-fA-F0-9]{4}){4}[a-fA-F0-9]{8})(,([a-fA-F0-9]{8}(-[a-fA-F0-9]{4}){4}[a-fA-F0-9]{8}))*$/", $vars['uuid']))
                {
                    $error_message = "redeliver received but UUID is not a valid CSV." . PHP_EOL . "UUIDs: " . $vars['uuid'];
                    trigger_error($error_message, E_USER_ERROR);
                }
                else
                {
                    // No need to explode; redeliver requests are always single UUIDs.
                    $redelivery = new attacher\MakeAttachment();
                    echo $redelivery->redeliverExisting($vars['uuid']); // This will throw an error if it doesn't work so we don't need to handle the return value.
                }
            }
            catch(Exception $e)
            {
                $trace = $e->getTrace();
                $trace_msg = "";
                foreach($trace as $item)
                {
                    $trace_msg .= $item['file'] . " on line " . $item['line'] . PHP_EOL;
                }
                $error_message = "redeliver failure. Error: " . $e->getMessage() . PHP_EOL . "Trace: " . $trace_msg;
                 // Kill the object before we throw the error.
                $redelivery = null;
                trigger_error($error_message, E_USER_ERROR);
            }
            finally
            {
                $redelivery = null;
            }
        }
    }
    else if($vars['action'] === "doAttach")
    {
        // An object is requesting an attachment using its provided ID!
        // Validate that we have the ID variable.
        if(!isset($vars['id']) || empty($vars['id']))
        {
            $error_message = "doAttach received but no ID specified.";
            trigger_error($error_message, E_USER_ERROR);
        }
        else
        {
            $id = $vars['id'];
        }
        try
        {
            // Let's make us a new object.
            $issueAttachment = new attacher\IssueAttachment();
            $result = $issueAttachment->issueAttachment($id);
            if($result)
            {
                echo $result;
            }
            else
            {
                echo "die";
            }
        }
        catch(Exception $e)
        {
            $trace = $e->getTrace();
            $trace_msg = "";
            foreach($trace as $item)
            {
                $trace_msg .= $item['file'] . " on line " . $item['line'] . PHP_EOL;
            }
            $error_message = "IssueAttachment failure. Error: " . $e->getMessage() . PHP_EOL . "Trace: " . $trace_msg;
             // Kill the object before we throw the error.
            $issueAttachment = null;
            trigger_error($error_message, E_USER_ERROR);
        }
        finally
        {
            $issueAttachment = null;
        }
    }
    else if($vars['action'] === "iAmAttached")
    {
        // Object has attached and is confirming that it has done so.
        // Object will contain the ID of the attachment and the UUID of the person it attached to.
        // Validate that we have the ID variable.
        if(!isset($vars['id']) || empty($vars['id']))
        {
            $error_message = "iAmAttached received but no ID specified.";
            trigger_error($error_message, E_USER_ERROR);
        }
        else
        {
            $id = $vars['id'];
        }
        // Validate that we have the UUID variable.
        if(!isset($vars['uuid']) || empty($vars['uuid']))
        {
            $error_message = "iAmAttached received but no UUID specified.";
            trigger_error($error_message, E_USER_ERROR);
        }
        else
        {
            $uuid_pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
            $uuid = $vars['uuid'];
            if (!preg_match($uuid_pattern, $uuid)) 
            {
                $error_message = "iAmAttached received but UUID is not a valid UUID." . PHP_EOL . "UUID: " . $uuid;
                trigger_error($error_message, E_USER_ERROR);
            }
        }
        // Let's create an object to validate attachment.
        try
        {
            $validateAttachment = new attacher\IssueAttachment();
            if(!$validateAttachment->successfulAttachment($id, $uuid))
            {
                $error_message = "iAmAttached received but attachment was not successful." . PHP_EOL . "ID: " . $id . PHP_EOL . "UUID: " . $uuid;
                $validateAttachment = null;
                trigger_error($error_message, E_USER_ERROR);
            }
        }
        catch(Exception $e)
        {
            // Stack trace this.
            $trace = $e->getTrace();
            $trace_msg = "";
            foreach($trace as $item)
            {
                $trace_msg .= $item['file'] . " on line " . $item['line'] . PHP_EOL;
            }
            $error_message = "iAmAttached received but a greater error occurred." . PHP_EOL . "ID: " . $id . PHP_EOL . "UUID: " . $uuid . PHP_EOL . $trace_msg;
            $validateAttachment = null;
            trigger_error($error_message, E_USER_ERROR);
        }
        finally
        {
            $validateAttachment = null;
        }
    }
?>