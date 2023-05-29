<?php
    namespace attacher;
    use PDO;
    class IssueAttachment
    {
        private $pdo;
        const DATE_FORMAT = 'Y-m-d H:i:s';
        function __construct()
        {
            $db = new \Database(); // Create the new database object so we can connect.
            $this->pdo = $db->connectToStarfall();
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        function successfulAttachment($attachment_id, $uuid)
        {
            echo $uuid , PHP_EOL, PHP_EOL;
            // This method will update the attachment table to reflect a successful attachment.
            try
            {
                $this->pdo->beginTransaction();
                $sql = "UPDATE attachments SET success = success + 1 WHERE id = ? AND uuid = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$attachment_id, $uuid]);
                $this->pdo->commit();
                return true;
            }
            catch(\PDOException $e)
            {
                return false;
            }
            finally
            {
                $this->pdo = null;
            }
        }
        
        function issueAttachment($attachment_id)
        {
            // Issues the attachment by returning a UUID belonging to the attachment id.
            try
            {
                $this->pdo->beginTransaction();
                $current_time = new \DateTime(); // Get the current time with the DateTime object.
                $sql = "SELECT uuid FROM attachments WHERE id = ? and timeout >= ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$attachment_id, $current_time->format(self::DATE_FORMAT)]);
                $this->pdo->commit();
                $uuid = $stmt->fetchColumn();
                if(!$uuid)
                {
                    return false;
                }
                return $uuid;
            }
            catch(\PDOException $e)
            {
                return false;
            }
            finally
            {
                $this->pdo = null;
            }
        }
    }
?>