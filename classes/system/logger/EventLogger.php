<?php
    // Program is loaded by every other class program, logging actions to the database.
    // Therefore it needs to be flexible.
    namespace logger;
    use PDO;
    class EventLogger
    {
        private $pdo;
        private $module;
        private $events = array( // Array containing types of events. Not zero-indexed.
            // We'll expand this as we add more events to the system.
            "character_creation", // 1
            "character_delection", // 2
            "character_saved", // 3
            "item_creation" // x
        );
        function __construct(PDO $pdo, $module)
        {
            // We are passing the PDO object of the logging class here so that we can catch inserts
            // into the same transactions.
            $this->pdo = $pdo;
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->module = $module; // This tells us which script is logging the event.
        }

        function logEvent($event, $data)
        {
            if($event === 0)
            {
                // This means I fucked up somewhere and something isn't being logged correctly.
                // Therefore we will simply throw an error.
                $error_message = "Event Logging failed with \$event = 0. Data: " . $data;
                throw $error_message;
            }
        }
    }
?>
