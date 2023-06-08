<?php
    namespace attacher;
    use PDO;
    class MakeAttachment
    {
        private $pdo;
        private $timeout = 120; // Timeout in seconds.
        const DATE_FORMAT = 'Y-m-d H:i:s';
        function __construct()
        {
            $db = new \Database();
            $this->pdo = $db->connectToStarfall(); 
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Verify that the PDO object still retains a good connection.
            if(!$this->pdo)
            {
                die("error::Could not connect to the database.");
            }
        }

        function redeliverExisting($uuid)
        {
            // Set the timeout on a row containing $uuid so that the attachment is deleted next time purgeOutdatedAttachments() is run.
            try
            {
                $this->pdo->beginTransaction();
                $current_time = new \DateTime(); // Get the current time with the DateTime object.
                $sql = "UPDATE attachments SET timeout = :timeout WHERE uuid = :uuid";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':timeout', $current_time->format(self::DATE_FORMAT), PDO::PARAM_STR);
                $stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
                $stmt->execute();
                $this->pdo->commit();
                return "1|".$uuid;
            }
            catch(\PDOException $e)
            {
                $this->pdo->rollBack();
                $this->pdo = null;
                $stmt = null;
                throw $e;
            }
            finally
            {
                $this->pdo = null;
                $stmt = null;
            }
            return false;
        }

        function purgeOutdatedAttachments()
        {
            // This method purges outdated attachments and truncates the table if it's empty after that.
            try 
            {
                $this->pdo->beginTransaction();
                if(!$this->pdo->inTransaction())
                {
                    exit ("error::Could not begin transaction.");
                    throw new \PDOException("Could not begin transaction.");
                }
                $status = $this->pdo->getAttribute(PDO::ATTR_SERVER_INFO);
                if ($status === false) 
                {
                    throw (new \PDOException('Database connection lost.'));
                }
                $current_time = new \DateTime(); // Get the current time with the DateTime object.
                $sql = "DELETE FROM attachments WHERE timeout <= :timeout OR success >= 2"; // Delete all attachments that have timed out.
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':timeout', $current_time->format(self::DATE_FORMAT), PDO::PARAM_STR);
                $stmt->execute();
                if ($this->pdo->inTransaction()) 
                {
                    $this->pdo->commit();
                } else 
                {
                    throw new \PDOException('No active transaction.');
                }
                $sql = "SELECT COUNT(*) FROM attachments"; // Count the number of rows in the table.
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $count = $stmt->fetchColumn(); // Fetch the column.
            
                if ($count == 0) // If the table is empty, truncate it.
                {
                    $sql = "TRUNCATE TABLE attachments";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute();
                }
            } 
            catch(\PDOException $e)
            {
                $this->pdo->rollBack();
                $this->pdo = null;
                throw $e;
            }
            finally
            {
                $stmt = null;
            }
        }
        

        function removeDuplicates(array $uuids)
        {
            // Removes UUIDs that already exist in the database but are not timed out.
            try
            {
                $current_time = new \DateTime(); // Get the current time with the DateTime object.
                $sql = "SELECT uuid FROM attachments WHERE uuid IN (" . implode(",", array_fill(0, count($uuids), "?")) . ") AND timeout > ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(array_merge($uuids, [$current_time->format(self::DATE_FORMAT)]));
                $uuids = array_diff($uuids, $stmt->fetchAll(PDO::FETCH_COLUMN));
            }
            catch(\PDOException $e)
            {
                $this->pdo->rollBack();
                $this->pdo = null;
                throw $e;
            }
            finally
            {
                $stmt = null;
            }
            return $uuids;
        }

        function prepareAttachments(array $uuids)
        {
            // This method prepares the attachments for the UUIDs provided.
            try
            {
                // First we purge!
                $this->purgeOutdatedAttachments();
                // Then we remove duplicates!
                $uuids = $this->removeDuplicates($uuids);
                if(count($uuids) == 0)
                {
                    $this->pdo = null;
                    return false; // If there are no UUIDs left, do nothing!
                }
                // Then we insert the UUIDs into the database.
                // First we set the timeout.
                $timeout = new \DateTime();
                $timeout->add((new \DateInterval('PT' . $this->timeout . 'S')));
                // Wildcard the UUIDs and insert them all with the same timeout.
                $sql = "INSERT INTO attachments (uuid, timeout) VALUES (?,?)";
                $this->pdo->beginTransaction();
                $stmt = $this->pdo->prepare($sql);
                foreach ($uuids as $uuid) {
                    $stmt->execute([$uuid, $timeout->format(self::DATE_FORMAT)]);
                }
                if($this->pdo->inTransaction())
                {
                    $this->pdo->commit();
                }
                else
                {
                    throw new \PDOException("No active transaction.");
                }
                // Then select all of our newly inserted rows and get their IDs.
                // Also validate that all were inserted successfully.
                $placeholders = implode(',', array_fill(0, count($uuids), '?'));
                $sql = "SELECT id FROM attachments WHERE uuid IN ($placeholders)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($uuids);
                $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (count($ids) != count($uuids)) {
                    throw new Exception("Not all UUIDs were inserted successfully.");
                }
                // Return the string "null" if there are no IDs.
                if (count($ids) == 0) {
                    return "null";
                }
                // Finally, return the IDs as a CSV.
                return implode(",", $ids);
            }
            catch(\PDOException $e)
            {
                $this->pdo = null;
                throw $e;
            }
            finally
            {
                $this->pdo = null;
                $stmt = null;
            }
        }
    }
?>