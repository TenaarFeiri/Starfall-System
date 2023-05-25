<?php
namespace attacher;
    class MakeAttachment
    {
        private $pdo;
        function __construct($pdo)
        {
            $this->pdo = $pdo; // Pass the PDO object reference into a private variable for use later.
            // Verify that the PDO object still retains a good connection.
            if(!$this->pdo)
            {
                die("error::Could not connect to the database.");
            }
        }

        function test()
        {
            echo "Test function called.";
        }
    }
?>