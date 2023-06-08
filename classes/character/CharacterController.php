<?php
    /*
        Character controller for the RP tool.
        Handles the creation and loading and saving of character data.
    */
    namespace attacher;
    use PDO;
    class CharacterController
    {
        // Properties we need to set up, if any.
        function __construct()
        {
            // Nothing, for now.
        }

        // Before all else, we need a way to create a character.
        function makeNewCharacter()
        {
            // The database will assign a default name to this new character.
            // We just need to perform the insert.
        }
    }
    
?>
