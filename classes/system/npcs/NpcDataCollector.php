<?php
    namespace system\npcs;
    class NpcDataCollector
    {
        // Class that will collect data from the database and return it to the NPC classes that need them.
        // This will handle all data that needs to be fetched.
        private $arr = array();
        private $npc_id;
        function __construct($npc_id)
        {
            $this->npc_id = $npc_id;
        }
    }
?>